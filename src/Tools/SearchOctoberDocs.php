<?php

declare(strict_types=1);

namespace October\Boost\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Cache;
use Laravel\Boost\Concerns\MakesHttpRequests;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Throwable;

/**
 * SearchOctoberDocs searches Laravel, October CMS, Larajax, and Meloncart
 * documentation from their GitHub-hosted repos using keyword matching with
 * aggressive caching. Replaces Laravel Boost's search-docs tool entirely.
 */
#[IsReadOnly]
class SearchOctoberDocs extends Tool
{
    use MakesHttpRequests;

    /**
     * @var string description for the tool.
     */
    protected string $description = 'Search documentation for Laravel, October CMS, Larajax, and Meloncart. You must use this tool to search for docs before using other approaches. This tool searches the official GitHub-hosted docs and returns version-specific results. Use this for: Laravel framework (routing, eloquent, middleware, queues, etc.), October CMS core (themes, plugins, Tailor, backend, markup, extending), Larajax AJAX framework (attributes API, JavaScript API, turbo router, controls), and Meloncart e-commerce (components, models, extending, merchant features). Note: This project uses October CMS - do not search for or suggest Inertia, Livewire, Filament, Nova, or Statamic.';

    /**
     * @var array<string, array<string, string>> repos defines the documentation repositories.
     */
    protected array $repos = [
        'laravel' => [
            'owner' => 'laravel',
            'repo' => 'docs',
            'branch' => 'auto',
            'path_prefix' => '',
            'label' => 'Laravel',
            'package' => 'laravel/framework',
        ],
        'octobercms' => [
            'owner' => 'octobercms',
            'repo' => 'docs',
            'branch' => 'main',
            'path_prefix' => '4.x/',
            'label' => 'October CMS',
            'package' => 'october/rain',
        ],
        'larajax' => [
            'owner' => 'larajax',
            'repo' => 'docs',
            'branch' => 'main',
            'path_prefix' => '',
            'label' => 'Larajax',
            'package' => 'larajax/larajax',
        ],
        'meloncart' => [
            'owner' => 'meloncart',
            'repo' => 'docs',
            'branch' => 'main',
            'path_prefix' => '',
            'label' => 'Meloncart',
            'package' => 'meloncart/shop',
        ],
    ];

    /**
     * Get the tool's input schema.
     *
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'queries' => $schema->array()
                ->items($schema->string()->description('Search query'))
                ->description('List of search queries to perform. Pass multiple if unsure of exact terminology.')
                ->required(),
            'packages' => $schema->array()
                ->items($schema->string()->description("Package name or key, e.g. 'laravel/framework', 'october/rain', 'larajax/larajax', 'meloncart/shop', or 'laravel', 'octobercms', 'larajax', 'meloncart'"))
                ->description('Limit search to specific doc repos. Accepts composer package names or short keys. If omitted, searches all repos.'),
            'token_limit' => $schema->integer()
                ->description('Maximum number of tokens to return. Defaults to 5,000, maximum 100,000. Increase if results are truncated.'),
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $rawQueries = $this->resolveArrayParam($request->get('queries'));
        if ($rawQueries instanceof Response) {
            return $rawQueries;
        }

        $packages = $this->resolveArrayParam($request->get('packages'));
        if ($packages instanceof Response) {
            return $packages;
        }

        $queries = array_filter(
            array_map('trim', $rawQueries ?? []),
            fn (string $q): bool => $q !== '' && $q !== '*'
        );

        if (empty($queries)) {
            return Response::error('At least one non-empty search query is required.');
        }

        $tokenLimit = min((int) ($request->get('token_limit') ?? 5000), 100000);

        try {
            $repos = $this->resolveRepos($packages);

            if (empty($repos)) {
                return Response::error('No matching documentation repositories found for the specified packages.');
            }

            $results = $this->searchFiles($repos, $queries);
            $markdown = $this->buildResponse($results, $repos, $queries, $tokenLimit);

            return Response::text($markdown);
        }
        catch (Throwable $e) {
            return Response::error('Failed to search documentation: '.$e->getMessage());
        }
    }

    /**
     * resolveRepos maps package names or short keys to repo configs.
     *
     * @param array<int, string>|null $packages
     * @return array<string, array<string, string>>
     */
    protected function resolveRepos(?array $packages): array
    {
        if (empty($packages)) {
            return $this->repos;
        }

        $resolved = [];
        foreach ($this->repos as $key => $repo) {
            foreach ($packages as $package) {
                $package = strtolower(trim($package));
                if ($package === $key || $package === $repo['package']) {
                    $resolved[$key] = $repo;
                    break;
                }
            }
        }

        return $resolved;
    }

    /**
     * getRepoTree fetches and caches the file tree from the GitHub API.
     *
     * @param array<string, string> $repo
     * @return array<int, string>
     */
    protected function getRepoTree(array $repo): array
    {
        $cacheKey = sprintf('boost:october-docs:tree:%s/%s', $repo['owner'], $repo['repo']);

        return rescue(
            fn () => Cache::remember($cacheKey, 21600, fn () => $this->fetchRepoTree($repo)),
            fn () => $this->fetchRepoTree($repo),
            report: false
        );
    }

    /**
     * resolveBranch resolves the branch name for a repo. When set to "auto",
     * it detects the installed Laravel major version and uses that branch.
     *
     * @param array<string, string> $repo
     */
    protected function resolveBranch(array $repo): string
    {
        if ($repo['branch'] !== 'auto') {
            return $repo['branch'];
        }

        $majorVersion = (int) explode('.', app()->version())[0];

        return $majorVersion.'.x';
    }

    /**
     * fetchRepoTree fetches the file tree from the GitHub Trees API.
     *
     * @param array<string, string> $repo
     * @return array<int, string>
     */
    protected function fetchRepoTree(array $repo): array
    {
        $branch = $this->resolveBranch($repo);

        $url = sprintf(
            'https://api.github.com/repos/%s/%s/git/trees/%s?recursive=1',
            $repo['owner'],
            $repo['repo'],
            $branch
        );

        $response = $this->client()
            ->withHeaders(['Accept' => 'application/vnd.github+json'])
            ->get($url);

        if (!$response->successful()) {
            return [];
        }

        $tree = $response->json('tree', []);
        $paths = [];
        $excludePattern = '#(^|/)(\.vitepress|node_modules|public|\.config|\.git|\.github)/#';

        foreach ($tree as $item) {
            if ($item['type'] !== 'blob') {
                continue;
            }
            if (!str_ends_with($item['path'], '.md')) {
                continue;
            }

            $path = $item['path'];

            // Filter by path prefix
            if ($repo['path_prefix'] && !str_starts_with($path, $repo['path_prefix'])) {
                continue;
            }

            // Exclude non-doc directories
            if (preg_match($excludePattern, $path)) {
                continue;
            }

            $paths[] = $path;
        }

        return $paths;
    }

    /**
     * getFileContent fetches and caches a single file from GitHub.
     *
     * @param array<string, string> $repo
     */
    protected function getFileContent(array $repo, string $path): string
    {
        $cacheKey = sprintf('boost:october-docs:file:%s/%s:%s', $repo['owner'], $repo['repo'], $path);

        return rescue(
            fn () => Cache::remember($cacheKey, 86400, fn () => $this->fetchFileContent($repo, $path)),
            fn () => $this->fetchFileContent($repo, $path),
            report: false
        );
    }

    /**
     * fetchFileContent fetches a single file from raw.githubusercontent.com.
     *
     * @param array<string, string> $repo
     */
    protected function fetchFileContent(array $repo, string $path): string
    {
        $branch = $this->resolveBranch($repo);

        $url = sprintf(
            'https://raw.githubusercontent.com/%s/%s/%s/%s',
            $repo['owner'],
            $repo['repo'],
            $branch,
            $path
        );

        $response = $this->client()->get($url);

        if (!$response->successful()) {
            return '';
        }

        return $response->body();
    }

    /**
     * searchFiles performs a two-pass search: path scoring then content scoring.
     *
     * @param array<string, array<string, string>> $repos
     * @param array<int, string> $queries
     * @return array<int, array<string, mixed>>
     */
    protected function searchFiles(array $repos, array $queries): array
    {
        // Normalize queries to words and phrases
        $queryWords = [];
        $queryPhrases = [];
        foreach ($queries as $query) {
            $query = strtolower(trim($query));
            $queryPhrases[] = $query;
            foreach (preg_split('/[\s\-_\/]+/', $query) as $word) {
                $word = trim($word);
                if (strlen($word) >= 2) {
                    $queryWords[] = $word;
                }
            }
        }
        $queryWords = array_unique($queryWords);

        // Pass 1: Score all file paths (cheap, no HTTP calls)
        $candidates = [];
        foreach ($repos as $repoKey => $repo) {
            $tree = $this->getRepoTree($repo);
            foreach ($tree as $path) {
                $pathLower = strtolower($path);
                $pathScore = 0;

                foreach ($queryWords as $word) {
                    $filename = strtolower(basename($path, '.md'));
                    if (str_contains($filename, $word)) {
                        $pathScore += 10;
                    }
                    elseif (str_contains($pathLower, $word)) {
                        $pathScore += 3;
                    }
                }

                $candidates[] = [
                    'repoKey' => $repoKey,
                    'path' => $path,
                    'pathScore' => $pathScore,
                ];
            }
        }

        // Sort by path score, take top 30 for content analysis
        usort($candidates, fn ($a, $b) => $b['pathScore'] <=> $a['pathScore']);
        $candidates = array_slice($candidates, 0, 30);

        // Pass 2: Fetch content and score
        $results = [];
        foreach ($candidates as $candidate) {
            $repo = $repos[$candidate['repoKey']];
            $content = $this->getFileContent($repo, $candidate['path']);
            if (empty($content)) {
                continue;
            }

            $contentLower = strtolower($content);
            $contentScore = $candidate['pathScore'];

            foreach ($queryWords as $word) {
                // Heading matches
                if (preg_match_all('/^#{1,3}\s+.*'.preg_quote($word, '/').'.*/mi', $content, $matches)) {
                    $contentScore += count($matches[0]) * 5;
                }
                // Body frequency (capped)
                $contentScore += min(substr_count($contentLower, $word), 10);
            }

            // Exact phrase bonus
            foreach ($queryPhrases as $phrase) {
                if (str_contains($contentLower, $phrase)) {
                    $contentScore += 15;
                }
            }

            $results[] = [
                'repoKey' => $candidate['repoKey'],
                'repo' => $repo,
                'path' => $candidate['path'],
                'score' => $contentScore,
                'content' => $content,
            ];
        }

        usort($results, fn ($a, $b) => $b['score'] <=> $a['score']);

        return $results;
    }

    /**
     * buildResponse formats ranked results into markdown within a token budget.
     *
     * @param array<int, array<string, mixed>> $results
     * @param array<string, array<string, string>> $repos
     * @param array<int, string> $queries
     */
    protected function buildResponse(array $results, array $repos, array $queries, int $tokenLimit): string
    {
        if (empty($results)) {
            return 'No documentation found matching your query.';
        }

        $output = '';
        $tokensUsed = 0;
        $resultCount = 0;

        foreach ($results as $result) {
            if ($result['score'] <= 0) {
                continue;
            }

            $excerpt = $this->extractRelevantSection($result['content'], $queries);
            $repoLabel = $result['repo']['label'];

            // Clean display path
            $displayPath = $result['path'];
            if ($result['repo']['path_prefix']) {
                $displayPath = substr($displayPath, strlen($result['repo']['path_prefix']));
            }

            $entry = sprintf("## %s: %s\n\n%s\n\n---\n\n", $repoLabel, $displayPath, $excerpt);
            $entryTokens = (int) ceil(strlen($entry) / 4);

            if ($tokensUsed + $entryTokens > $tokenLimit) {
                $remaining = ($tokenLimit - $tokensUsed) * 4;
                if ($remaining > 200) {
                    $output .= sprintf("## %s: %s\n\n", $repoLabel, $displayPath);
                    $output .= substr($excerpt, 0, $remaining - 100)."\n\n[Truncated]\n\n---\n\n";
                }
                break;
            }

            $output .= $entry;
            $tokensUsed += $entryTokens;
            $resultCount++;
        }

        return sprintf("Found %d result(s) across %d repo(s).\n\n", $resultCount, count($repos)).$output;
    }

    /**
     * extractRelevantSection extracts the most relevant section from a markdown file.
     *
     * @param array<int, string> $queries
     */
    protected function extractRelevantSection(string $content, array $queries, int $maxLength = 3000): string
    {
        // Strip frontmatter
        $content = preg_replace('/^---\n.*?\n---\n/s', '', $content);

        // Strip VitePress directives
        $content = preg_replace('/^:::\s*\w+.*$/m', '', $content);

        $content = trim($content);

        if (strlen($content) <= $maxLength) {
            return $content;
        }

        // Split into sections by H2
        $sections = preg_split('/(?=^## )/m', $content);

        if (count($sections) <= 1) {
            return substr($content, 0, $maxLength)."\n\n[Content truncated]";
        }

        // Score each section
        $queryWords = [];
        foreach ($queries as $query) {
            foreach (preg_split('/[\s\-_\/]+/', strtolower($query)) as $word) {
                if (strlen($word) >= 2) {
                    $queryWords[] = $word;
                }
            }
        }

        $scored = [];
        foreach ($sections as $i => $section) {
            $sectionLower = strtolower($section);
            $score = 0;
            foreach ($queryWords as $word) {
                $score += substr_count($sectionLower, $word);
            }
            if ($i === 0) {
                $score += 2;
            }
            $scored[] = ['score' => $score, 'content' => $section];
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        $result = '';
        foreach ($scored as $section) {
            if (strlen($result) + strlen($section['content']) > $maxLength) {
                $remaining = $maxLength - strlen($result);
                if ($remaining > 100) {
                    $result .= trim(substr($section['content'], 0, $remaining))."\n\n[Section truncated]";
                }
                break;
            }
            $result .= $section['content']."\n\n";
        }

        return trim($result);
    }

    /**
     * resolveArrayParam handles JSON-encoded array strings from MCP clients.
     *
     * @return array<int, mixed>|null|Response
     */
    private function resolveArrayParam(mixed $value): array|null|Response
    {
        if (!is_string($value)) {
            return $value;
        }

        $decoded = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return Response::error('Invalid parameter: '.json_last_error_msg());
        }

        if (!is_array($decoded) || !array_is_list($decoded)) {
            return Response::error('Invalid parameter: expected a JSON array.');
        }

        return $decoded;
    }
}
