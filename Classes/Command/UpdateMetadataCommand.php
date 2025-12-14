<?php

declare(strict_types=1);

namespace Ayacoo\Podigee\Command;

use Ayacoo\Podigee\Domain\Repository\FileRepository;
use Ayacoo\Podigee\Helper\PodigeeHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Index\MetaDataRepository;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\AbstractOnlineMediaHelper;
use TYPO3\CMS\Core\Resource\ProcessedFileRepository;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[AsCommand('podigee:updateMetadata', 'Updates the Podigee metadata')]
class UpdateMetadataCommand extends Command
{
    protected function configure(): void
    {
        $this->setDescription('Updates the Podigee metadata');
        $this->addOption(
            'limit',
            null,
            InputOption::VALUE_OPTIONAL,
            'Defines the number of podigee podcasts to be checked',
            10
        );
    }

    public function __construct(
        protected FileRepository $fileRepository,
        protected MetaDataRepository $metadataRepository,
        protected ResourceFactory $resourceFactory,
        protected ProcessedFileRepository $processedFileRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $limit = (int)($input->getOption('limit') ?? 10);

        $podigeeHelper = GeneralUtility::makeInstance(PodigeeHelper::class, 'podigee');

        $videos = $this->fileRepository->getVideosByFileExtension('podigee', $limit);
        foreach ($videos as $video) {
            $file = $this->resourceFactory->getFileObject($video['uid']);
            $metaData = $podigeeHelper->getMetaData($file);
            if (!empty($metaData)) {
                $newMetaData = [
                    'width' => (int)$metaData['width'],
                    'height' => (int)$metaData['height'],
                    'podigee_thumbnail' => $metaData['podigee_thumbnail'],
                ];
                if (isset($metaData['title'])) {
                    $newMetaData['title'] = $metaData['title'];
                }
                if (isset($metaData['author'])) {
                    $newMetaData['author'] = $metaData['author'];
                }
                $this->metadataRepository->update($file->getUid(), $newMetaData);
                $this->handlePreviewImage($podigeeHelper, $file);
                $io->success($file->getProperty('title') . '(UID: ' . $file->getUid() . ') was processed');
            }
        }

        return Command::SUCCESS;
    }

    protected function handlePreviewImage(AbstractOnlineMediaHelper $onlineMediaHelper, File $file): void
    {
        $processedFiles = $this->processedFileRepository->findAllByOriginalFile($file);
        foreach ($processedFiles as $processedFile) {
            $processedFile->delete();
        }

        $videoId = $onlineMediaHelper->getOnlineMediaId($file);
        $temporaryFileName = $this->getTempFolderPath() . $file->getExtension() . '_' . md5($videoId) . '.jpg';
        if (file_exists($temporaryFileName)) {
            unlink($temporaryFileName);
        }
        $onlineMediaHelper->getPreviewImage($file);
    }

    protected function getTempFolderPath(): string
    {
        $path = Environment::getPublicPath() . '/typo3temp/assets/online_media/';
        if (!is_dir($path)) {
            GeneralUtility::mkdir_deep($path);
        }
        return $path;
    }
}
