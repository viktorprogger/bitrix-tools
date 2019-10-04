<?php
/** @noinspection PhpUnhandledExceptionInspection */

namespace adapt\tools\migrations;

use Arrilot\BitrixMigrations\BaseMigrations\BitrixMigration;
use Arrilot\BitrixMigrations\Exceptions\MigrationException;
use Bitrix\Main\Loader;
use CFile;
use CIBlockElement;
use CIBlockSection;
use CSearch;
use CUtil;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * @mixin BitrixMigration
 */
trait CSVTrait
{
    private $sectionCache;

    protected function importCsv()
    {
        if (!Loader::includeModule('search')) {
            throw new MigrationException('Can\'t load module "search"');
        }

        if (!$file = fopen($this->getCsvPath(), 'rb')) {
            /** @noinspection PhpUnhandledExceptionInspection */
            throw new MigrationException('There is no csv file to import');
        }

        $iblockId = $this->getIblockIdByCode($this->getIblockCode());

        $output = new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG);
        $output->writeln('Creating iblock elements');

        $progress = new ProgressBar($output);
        $progress->start($this->getCsvCount($file));

        $element = new CIBlockElement();

        while ($record = fgetcsv($file)) {
            $progress->advance();

            $sections = [];
            foreach ($this->getImportDefinitionSections() as $sectionKey) {
                $sections[] = $record[$sectionKey];
            }
            $sectionId = $this->getSectionId($sections);

            $fields = [
                'IBLOCK_ID'         => $iblockId,
                'IBLOCK_SECTION_ID' => $sectionId,
            ];
            foreach ($this->getImportDefinitionFields() as $code => $csvKey) {
                $fields[$code] = $record[$csvKey];
            }
            foreach ($this->getImportDefinitionProperties() as $code => $csvKey) {
                if ($record[$csvKey] !== '') {
                    if (in_array($code, $this->getFileProperties(), true)) {
                        $fields['PROPERTY_VALUES'][$code] = $this->getFileArray($record[$csvKey]);
                    } else {
                        $fields['PROPERTY_VALUES'][$code] = $record[$csvKey];
                    }
                }
            }

            $element->Add($fields, false, false);
        }

        $progress->finish();
        $output->writeln('');
        $output->writeln('Indexing new elements...');
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        CSearch::ReindexModule('iblock');
        $output->writeln('Index is ready.');
    }

    private function getCsvCount($handler)
    {
        $result = 0;
        while (fgetcsv($handler)) {
            $result++;
        }
        rewind($handler);

        return $result;
    }

    /**
     * TODO Поиск уже имеющихся в БД разделов по имени и иерархии
     *
     * @param string[] $sections
     *
     * @return int
     * @throws MigrationException
     */
    private function getSectionId($sections)
    {
        $sections = array_filter($sections);
        if (count($sections) === 0) {
            return 0;
        }

        $hash = md5(implode('|', $sections));
        if (empty($this->sectionCache[$hash])) {
            $sectionCurrent = array_pop($sections);
            $parentId = $this->getSectionId(...$sections);
            $fields = [
                'NAME'              => $sectionCurrent,
                'CODE'              => CUtil::translit($sectionCurrent, 'ru', [
                    'replace_space' => '-',
                    'replace_other' => '-',
                ]),
                'IBLOCK_SECTION_ID' => $parentId,
                'IBLOCK_ID'         => $this->getIblockIdByCode($this->getIblockCode()),
            ];

            $section = new CIBlockSection();
            $id = $section->Add($fields);
            if (!$id) {
                throw new MigrationException('Error while adding a section: ' . $section->LAST_ERROR);
            }

            $this->sectionCache[$hash] = $id;
        }

        return $this->sectionCache[$hash];
    }

    /**
     * @return array
     */
    protected function getImportDefinitionFields()
    {
        return [
            'XML_ID'       => 0,
            'CODE'         => 1,
            'NAME'         => 2,
            'ACTIVE'       => 3,
            'ACTIVE_FROM'  => 4,
            'ACTIVE_TO'    => 5,
            'PREVIEW_TEXT' => 6,
            'DETAIL_TEXT'  => 7,
            'SORT'         => 8,
        ];
    }

    protected function getFileProperties() {
        return [];
    }

    /**
     * @param string $path Path to the file beginning with 'upload/iblock'
     *
     * @return array
     * @throws MigrationException
     */
    private function getFileArray($path)
    {
        $path = array_filter(explode('/', $path));
        array_shift($path);
        array_shift($path);
        $dir = pathinfo($this->getCsvPath(), PATHINFO_FILENAME);
        $path = dirname(dirname(__DIR__)) . "/migrations/data/$dir/" . implode('/', $path);

        if (!file_exists($path)) {
            throw new MigrationException('File not found: ' . $path);
        }

        return CFile::MakeFileArray($path);
    }

    /**
     * @return string
     */
    abstract protected function getIblockCode();

    /**
     * @return array
     */
    abstract protected function getImportDefinitionSections();

    /**
     * @return array
     */
    abstract protected function getImportDefinitionProperties();

    /**
     * @return string
     */
    abstract protected function getCsvPath();
}
