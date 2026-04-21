<?php

declare(strict_types=1);

namespace October\Boost\Tools;

use Illuminate\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetThemeStructure extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Get the structure of the active October CMS theme, including pages (with URLs), layouts, partials, and content files. Use this to understand the frontend template structure before creating or modifying theme files.';

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'include_components' => $schema->boolean()
                ->description('Include the components attached to each page. Defaults to false.'),
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $includeComponents = $request->get('include_components', false);

        try {
            $theme = \Cms\Classes\Theme::getActiveTheme();

            if (!$theme) {
                return Response::error('No active theme found.');
            }

            $data = [
                'theme' => $theme->getDirName(),
                'path' => $theme->getPath(),
            ];

            $data['pages'] = $this->getPages($theme, $includeComponents);
            $data['layouts'] = $this->getLayouts($theme);
            $data['partials'] = $this->getPartials($theme);

            return Response::json($data);
        }
        catch (\Throwable $e) {
            return Response::error('Failed to read theme structure: '.$e->getMessage());
        }
    }

    /**
     * getPages returns all pages in the theme.
     */
    protected function getPages($theme, bool $includeComponents): array
    {
        $pages = [];

        foreach (\Cms\Classes\Page::listInTheme($theme) as $page) {
            $entry = [
                'fileName' => $page->getFileName(),
                'title' => $page->title,
                'url' => $page->url,
                'layout' => $page->layout,
            ];

            if ($includeComponents && $page->settings && isset($page->settings['components'])) {
                $entry['components'] = array_keys($page->settings['components']);
            }

            $pages[] = $entry;
        }

        return $pages;
    }

    /**
     * getLayouts returns all layouts in the theme.
     */
    protected function getLayouts($theme): array
    {
        $layouts = [];

        foreach (\Cms\Classes\Layout::listInTheme($theme) as $layout) {
            $layouts[] = [
                'fileName' => $layout->getFileName(),
                'description' => $layout->description ?? '',
            ];
        }

        return $layouts;
    }

    /**
     * getPartials returns all partials in the theme.
     */
    protected function getPartials($theme): array
    {
        $partials = [];

        foreach (\Cms\Classes\Partial::listInTheme($theme) as $partial) {
            $partials[] = [
                'fileName' => $partial->getFileName(),
                'description' => $partial->description ?? '',
            ];
        }

        return $partials;
    }
}
