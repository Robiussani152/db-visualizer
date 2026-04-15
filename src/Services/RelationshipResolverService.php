<?php

namespace Naimul\DbVisualizer\Services;

use Illuminate\Database\Eloquent\Relations\Relation;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

class RelationshipResolverService
{
    protected RelationUsageAnalyzerService $usageAnalyzer;

    public function __construct(RelationUsageAnalyzerService $usageAnalyzer)
    {
        $this->usageAnalyzer = $usageAnalyzer;
    }

    public function resolve($model): array
    {
        $relations = [];

        $reflection = new ReflectionClass($model);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {

            // Only model's own methods
            if ($method->class !== get_class($model)) {
                continue;
            }

            // Skip methods with parameters
            if ($method->getNumberOfParameters() > 0) {
                continue;
            }

            // Skip magic methods
            if (str_starts_with($method->getName(), '__')) {
                continue;
            }

            try {

                $result = $this->safeInvoke($model, $method);

                if ($result instanceof Relation) {

                    $relationName = $method->getName();

                    // USAGE DETECTION
                    $isUsed = $this->usageAnalyzer->isRelationUsed($relationName);

                    // N+1 DETECTION
                    $nPlusOne = $this->usageAnalyzer->detectNPlusOne($relationName);

                    // EAGER LOAD CHECK
                    $isEagerLoaded = $this->usageAnalyzer->isEagerLoaded($relationName);

                    $relations[] = [
                        'method' => $relationName,
                        'type' => class_basename($result),
                        'related' => class_basename(get_class($result->getRelated())),

                        // intelligence data
                        'used' => $isUsed,
                        'n_plus_one' => $nPlusOne,
                        'missing_eager' => $isUsed && ! $isEagerLoaded,
                    ];
                }

            } catch (Throwable $e) {
                // silently skip broken methods
                continue;
            }
        }

        return $relations;
    }

    /**
     * SAFE EXECUTION (NO SIDE EFFECT RISK)
     */
    protected function safeInvoke($model, ReflectionMethod $method)
    {
        $instance = clone $model;

        // prevent already-loaded relations reuse
        if (method_exists($instance, 'unsetRelations')) {
            $instance->unsetRelations();
        }

        return $method->invoke($instance);
    }
}
