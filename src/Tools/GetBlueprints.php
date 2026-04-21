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
        if (!class_exists(\Tailor\Classes\Blueprint::class)) {
            return Response::error('Tailor module is not available in this October CMS installation.');
        }

        $summary = $request->get('summary', true);
        $handle = $request->get('handle');

        try {
            if ($handle) {
                return $this->getDetailedBlueprint($handle);
            }

            return $this->getBlueprintSummary($summary);
        }
        catch (\Throwable $e) {
            return Response::error('Failed to read blueprints: '.$e->getMessage());
        }
    }

    /**
     * getDetailedBlueprint returns full details for a single blueprint.
     */
    protected function getDetailedBlueprint(string $handle): Response
    {
        $blueprint = \Tailor\Classes\BlueprintIndexer::instance()->findByHandle($handle);

        // Indexer only finds sections and globals, fall back to listInProject for mixins
        if (!$blueprint) {
            foreach (\Tailor\Classes\Blueprint::listInProject() as $bp) {
                if ($bp->handle === $handle) {
                    $blueprint = $bp;
                    break;
                }
            }
        }

        if (!$blueprint) {
            return Response::error("Blueprint with handle '{$handle}' not found.");
        }

        $data = [
            'handle' => $blueprint->handle,
            'type' => $blueprint->type,
            'name' => $blueprint->name,
            'fileName' => $blueprint->fileName,
        ];

        if ($tableNames = $this->getTableNames($blueprint)) {
            $data['tableNames'] = $tableNames;
        }

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
    protected function getBlueprintSummary(bool $summary): Response
    {
        $blueprints = [];

        foreach (\Tailor\Classes\Blueprint::listInProject() as $blueprint) {
            $entry = [
                'handle' => $blueprint->handle,
                'type' => $blueprint->type,
                'name' => $blueprint->name,
            ];

            if ($tableName = $blueprint->getContentTableName()) {
                $entry['contentTableName'] = $tableName;
            }

            if (!$summary && isset($blueprint->fields)) {
                $entry['fields'] = $blueprint->fields;
            }

            $blueprints[] = $entry;
        }

        return Response::json(['blueprints' => $blueprints]);
    }

    /**
     * getTableNames returns all database table names for a blueprint.
     */
    protected function getTableNames(\Tailor\Classes\Blueprint $blueprint): array
    {
        $tables = [];

        if ($name = $blueprint->getContentTableName()) {
            $tables['content'] = $name;
        }

        if ($name = $blueprint->getJoinTableName()) {
            $tables['join'] = $name;
        }

        if ($name = $blueprint->getRepeaterTableName()) {
            $tables['repeater'] = $name;
        }

        return $tables;
    }
}
