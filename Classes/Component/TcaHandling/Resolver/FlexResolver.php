<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\Resolver;

use In2code\In2publishCore\Component\TcaHandling\Demands;
use In2code\In2publishCore\Component\TcaHandling\PreProcessing\Service\FlexFormFlatteningService;
use In2code\In2publishCore\Component\TcaHandling\PreProcessing\TcaPreProcessingService;
use In2code\In2publishCore\Domain\Model\DatabaseEntityRecord;
use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Domain\Model\VirtualFlexFormRecord;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Service\FlexFormService;

use function array_keys;
use function array_merge;
use function array_pop;
use function array_unique;
use function implode;
use function is_array;
use function json_decode;

use const JSON_THROW_ON_ERROR;

class FlexResolver implements Resolver
{
    protected FlexFormTools $flexFormTools;
    protected FlexFormService $flexFormService;
    protected FlexFormFlatteningService $flexFormFlatteningService;
    protected TcaPreProcessingService $tcaPreProcessingService;
    protected string $table;
    protected string $column;
    protected array $processedTca;

    public function __construct(
        FlexFormTools $flexFormTools,
        FlexFormService $flexFormService,
        FlexFormFlatteningService $flexFormFlatteningService,
        TcaPreProcessingService $tcaPreProcessingService,
        string $table,
        string $column,
        array $processedTca
    ) {
        $this->flexFormTools = $flexFormTools;
        $this->flexFormService = $flexFormService;
        $this->flexFormFlatteningService = $flexFormFlatteningService;
        $this->tcaPreProcessingService = $tcaPreProcessingService;
        $this->table = $table;
        $this->column = $column;
        $this->processedTca = $processedTca;
    }

    public function resolve(Demands $demands, Record $record): void
    {
        if (!($record instanceof DatabaseEntityRecord)) {
            return;
        }
        $dataStructureIdentifierJson = $this->flexFormTools->getDataStructureIdentifier(
            ['config' => $this->processedTca],
            $this->table,
            $this->column,
            $record->getLocalProps() ?: $record->getForeignProps()
        );
        $dataStructureKey = json_decode(
            $dataStructureIdentifierJson,
            true,
            512,
            JSON_THROW_ON_ERROR
        )['dataStructureKey'];

        $localValues = $record->getLocalProps()[$this->column] ?? [];
        if ([] !== $localValues) {
            $localValues = $this->flexFormService->convertFlexFormContentToArray($localValues);
            $localValues['pid'] = $record->getProp('pid');
        }
        $localValues = $this->flattenFlexFormData($localValues);
        $foreignValues = $record->getForeignProps()[$this->column] ?? [];
        if ([] !== $foreignValues) {
            $foreignValues = $this->flexFormService->convertFlexFormContentToArray($foreignValues);
            $foreignValues['pid'] = $record->getProp('pid');
        }
        $localValues = $this->flattenFlexFormData($localValues);

        $flexFormFields = array_unique(array_merge(array_keys($localValues), array_keys($foreignValues)));

        $flexFormTableName = $this->table . '/' . $this->column . '/' . $dataStructureKey;
        $virtualRecord = new VirtualFlexFormRecord($record, $flexFormTableName, $localValues, $foreignValues);

        $compatibleTcaParts = $this->tcaPreProcessingService->getCompatibleTcaParts();

        foreach ($flexFormFields as $flexFormField) {
            /** @var Resolver $resolver */
            $resolver = $compatibleTcaParts[$flexFormTableName][$flexFormField]['resolver'] ?? null;
            if (null !== $resolver) {
                $resolver->resolve($demands, $virtualRecord);
            }
        }
    }

    protected function flattenFlexFormData(array $data, array $path = []): array
    {
        $newData = [];
        foreach ($data as $key => $value) {
            $path[] = $key;
            if (is_array($value)) {
                foreach ($this->flattenFlexFormData($value, $path) as $subKey => $subVal) {
                    $newData[$subKey] = $subVal;
                }
            } else {
                $newData[implode('.', $path)] = $value;
            }
            array_pop($path);
        }
        return $newData;
    }

}
