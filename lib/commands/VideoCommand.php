<?php

namespace adapt\tools\commands;

use adapt\tools\DTO\Video\AbstractFileDTO;
use adapt\tools\DTO\Video\FileLong;
use adapt\tools\DTO\Video\FileShort;
use CIBlockElement;
use CModule;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class VideoCommand extends Command
{
    const IB_ID = 10;
    const PROPERTY_VIDEO = 'VIDEO';
    const PROPERTY_PK = 'PROPERTY_PRIMARYKEY';
    const PROPERTY_SK = 'PROPERTY_SHORT_KEY';
    const DIRECTORY_UPLOAD = 'upload/video';
    const DIRECTORY_LONG = 'long';
    const DIRECTORY_SHORT = 'short';
    const VIDEO_WIDTH = 400;
    const VIDEO_HEIGHT = 300;
    const VIDEO_DURATION_LONG = 300;
    const VIDEO_DURATION_SHORT = 20;

    private $db;

    private $videoExtensions = array('mp4', 'mov');
    private $successfulLong = array();
    private $successfulShort = array();
    private $notFound = array();
    private $failed = array();
    private $directoryRoot = '';

    private $path = '';

    protected static $defaultName = 'video:process';

    protected function configure()
    {
        parent::configure();
        $this->db = $GLOBALS['DB'];

        $this->directoryRoot = $_SERVER['DOCUMENT_ROOT'];

        $this->addArgument('path', InputArgument::REQUIRED, 'Video directory (after upload/video/)');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->path = $input->getArgument('path');
        CModule::IncludeModule('iblock');
        $count = $this->getFileCount();

        $output->writeln('Start adding videos');
        $output->writeln("Found $count elements");

        $outputProgress= clone $output;
        $outputProgress->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
        $progress = new ProgressBar($outputProgress);
        $progress->start($count);

        foreach ($this->getFiles() as $fileDTO) {
            /** @noinspection DisconnectedForeachInstructionInspection */
            $progress->advance();
            $this->fileProcess($fileDTO);
        }

        $progress->finish();
        $output->writeln('');

        $this->printResult($output);
    }

    private function getFileCount()
    {
        $result = 0;

        foreach (array($this->getDirectoryLong(), $this->getDirectoryShort()) as $dir) {
            $files = scandir($dir);
            foreach ($files as $file) {
                if ($this->fileSuites($dir . DIRECTORY_SEPARATOR . $file)) {
                    $result++;
                }
            }
        }

        return $result;
    }

    /**
     * @return AbstractFileDTO[]
     */
    private function getFiles()
    {
        $result  = array();
        if (is_dir($this->getDirectoryLong())) {
            foreach (scandir($this->getDirectoryLong()) as $file) {
                $filepath = $this->getDirectoryLong() . DIRECTORY_SEPARATOR . $file;

                if ($this->fileSuites($filepath)) {
                    $result[] = new FileLong($filepath);
                }
            }
        }

        if (is_dir($this->getDirectoryShort())) {
            foreach (scandir($this->getDirectoryShort()) as $file) {
                $filepath = $this->getDirectoryShort() . DIRECTORY_SEPARATOR . $file;

                if ($this->fileSuites($filepath)) {
                    $result[] = new FileShort($filepath);
                }
            }
        }

        return $result;
    }

    /**
     * @param string $filepath
     *
     * @return bool
     */
    public function fileSuites($filepath)
    {
        $pathinfo = pathinfo($filepath);

        return (file_exists($filepath) && !is_dir($filepath) && in_array($pathinfo['extension'], $this->videoExtensions, true));
    }

    /**
     * @param AbstractFileDTO $fileDTO
     */
    private function fileProcess($fileDTO)
    {
        $found = false;

        foreach ($this->getElements($fileDTO) as $element) {
            $found = true;

            $property = array(
                array(
                    'VALUE' => array(
                        'path'     => $fileDTO->getPathRelative(),
                        'width'    => self::VIDEO_WIDTH,
                        'height'   => self::VIDEO_HEIGHT,
                        'title'    => '',
                        'duration' => $fileDTO->isShort() ? self::VIDEO_DURATION_SHORT : self::VIDEO_DURATION_LONG,
                        'author'   => '',
                        'date'     => '',
                        'desc'     => '',
                    ),
                ),
            );


            /** @noinspection DynamicInvocationViaScopeResolutionInspection */
            /** @noinspection PhpDynamicAsStaticMethodCallInspection */
            if (CIBlockElement::SetPropertyValueCode($element["ID"], self::PROPERTY_VIDEO, $property)) {
                if ($fileDTO->isShort()) {
                    $this->successfulShort[] = $fileDTO->getName();
                } else {
                    $this->successfulLong[] = $fileDTO->getName();
                }
            } else {
                $this->failed[] = $fileDTO->getPath();
            }
        }

        if ($found === false) {
            $this->notFound[] = $fileDTO->getName();
        }
    }

    /**
     * @param AbstractFileDTO $fileDTO
     *
     * @return array
     */
    private function getElements($fileDTO)
    {
        $result = array();
        $select = array('ID', 'IBLOCK_ID');
        $filter = array('IBLOCK_ID' => self::IB_ID);
        if ($fileDTO->isShort()) {
            $filter[] = array(
                self::PROPERTY_PK => $fileDTO->getChildCode(),
                self::PROPERTY_SK => $fileDTO->getChildCode(),
                'LOGIC'           => 'OR',
            );
        } else {
            $filter[self::PROPERTY_PK] = $fileDTO->getChildCode();
        }

        /** @noinspection DynamicInvocationViaScopeResolutionInspection */
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $resource = CIBlockElement::GetList(array(), $filter, false, false, $select);
        while ($item = $resource->Fetch()) {
            $result[] = $item;
        }

        return $result;
    }

    /**
     * @return string
     */
    private function getDirectoryLong()
    {
        return $this->directoryRoot
            . DIRECTORY_SEPARATOR
            . self::DIRECTORY_UPLOAD
            . DIRECTORY_SEPARATOR
            . $this->path
            . DIRECTORY_SEPARATOR
            . self::DIRECTORY_LONG;
    }

    private function getDirectoryShort()
    {
        return $this->directoryRoot
            . DIRECTORY_SEPARATOR
            . self::DIRECTORY_UPLOAD
            . DIRECTORY_SEPARATOR
            . $this->path
            . DIRECTORY_SEPARATOR
            . self::DIRECTORY_SHORT;
    }

    /**
     * @param OutputInterface $output
     */
    private function printResult(OutputInterface $output)
    {
        $output->writeln(sprintf('Successfully processed %d long videos and %d short videos', count($this->successfulLong), count($this->successfulShort)));

        if (count($this->notFound)) {
            $output->writeln('We didn\'t find kids for these files:');
            foreach ($this->notFound as $item) {
                $output->writeln(sprintf('--> %s', $item));
            }
            $output->writeln('');
        } else {
            $output->writeln('Kids are found for all the files!');
        }

        if (count($this->failed)) {
            $output->writeln('These files are failed:');
            foreach ($this->failed as $item) {
                $output->writeln(sprintf('--> %s', $item));
            }
        } else {
            $output->writeln('All found kids are processed successfully.');
        }
    }
}
