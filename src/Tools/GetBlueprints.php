<?php

declare(strict_types=1);

namespace October\Boost\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetBlueprints extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = 'List all October CMS Tailor blueprints in the application, including their handle, type, name, and fields. Use "summary" mode first to see all blueprints, then request full details for a specific handle. Useful for understanding the content structure before modifying blueprints or querying Tailor entries.';

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'summary' => $schema->boolean()
                ->description('Return only blueprint handles, types, and names. Use this first to understand the content structure. Defaults to true.'),
            'handle' => $schema->string()
                ->description('Get full details (including all fields) for a specific blueprint handle, e.g. "Blog\\Post".'),
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        if (!class_exists(\Tailor\Classes\BlueprintIndexer::class)) {
            return Response::error('Tailor module is not available in this October CMS installation.');
        }

        $summary = $request->get('summary', true);
        $handle = $request->get('handle');

        try {
            $indexer = \Tailor\Classes\BlueprintIndexer::instance();

            if ($handle) {
                return $this->getDetailedBlueprint($indexer, $handle);
            }

            return $this->getBlueprintSummary($indexer, $summary);
        }
        catch (\Throwable $e) {
            return Response::error('Failed to read blueprints: '.$e->getMessage());
        }
    }

    /**
     * getDetailedBlueprint returns full details for a single blueprint.
     */
    protected function getDetailedBlueprint($indexer, string $handle): Response
    {
        $blueprint = $indexer->findByHandle($handle);

        if (!$blueprint) {
            return Response::error("Blueprint with handle '{$handle}' not found.");
        }

        $data = [
            'handle' => $blueprint->handle,
            'type' => $blueprint->type,
            'name' => $blueprint->name,
            'fileName' => $blueprint->fileName,
        ];

        if (isset($blueprint->drafts)) {
            $data['drafts'] = $blueprint->drafts;
        }

        if (isset($blueprint->fields)) {
            $data['fields'] = $blueprint->fields;
        }

        if (isset($blueprint->navigation)) {
            $data['navigation'] = $blueprint->navigation;
        }

        return Response::json($data);
    }

    /**
     * getBlueprintSummary returns a list of all blueprints.
     */
    protected function getBlueprintSummary($indexer, bool $summary): Response
    {
        $blueprints = [];

        foreach ($indexer->listBlueprints() as $blueprint) {
            $entry = [
                'handle' => $blueprint->handle,
                'type' => $blueprint->type,
                'name' => $blueprint->name,
            ];

            if (!$summary && isset($blueprint->fields)) {
                $entry['fields'] = $blueprint->fields;
            }

            $blueprints[] = $entry;
        }

        return Response::json(['blueprints' => $blueprints]);
    }
}
