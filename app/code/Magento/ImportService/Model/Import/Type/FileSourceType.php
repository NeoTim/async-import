<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ImportService\Model\Import\Type;

use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\ImportService\ImportServiceException;
use Magento\ImportService\Api\SourceRepositoryInterface;
use Magento\ImportService\Api\Data\SourceInterface;
use Magento\Framework\DataObject\IdentityGeneratorInterface as IdentityGenerator;

/**
 * Generic Source Type
 */
class FileSourceType implements SourceTypeInterface
{
    /**
     * @var SourceRepositoryInterface
     */
    private $sourceRepository;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var IdentityGeneratorInterface
     */
    private $identityGenerator;

    /**
     * @var string
     */
    private $sourceType;

    /**
     * @var string
     */
    private $mime;

    /**
     * CSV File Type constructor.
     *
     * @param SourceRepositoryInterface $sourceRepository
     * @param Filesystem $filesystem
     * @param IdentityGenerator $identityGenerator
     * @param string $sourceType
     * @param string $mime
     */
    public function __construct(
        SourceRepositoryInterface $sourceRepository,
        Filesystem $filesystem,
        IdentityGenerator $identityGenerator,
        $sourceType = null,
        $mime = null
    ) {
        $this->sourceRepository = $sourceRepository;
        $this->filesystem = $filesystem;
        $this->identityGenerator = $identityGenerator;
        $this->sourceType = $sourceType;
        $this->mime = $mime;
    }

    /**
     * save source content
     *
     * @param SourceInterface $source
     * @throws ImportServiceException
     * @return SourceInterface
     */
    public function save(SourceInterface $source)
    {
        /** @var string $uuid */
        $uuid = $source->getUuid() ?: $this->identityGenerator->generateId();

        /** @var string $fileName */
        $fileName = $uuid;

        /** @var string $contentFilePath */
        $contentFilePath =  SourceTypeInterface::IMPORT_SOURCE_FILE_PATH . $fileName;

        /** @var Magento\Framework\Filesystem\Directory\Write $var */
        $var = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);

        if (!$var->writeFile($contentFilePath, $source->getImportData())) {
            /** @var array $lastError */
            $lastError = error_get_last();

            /** @var string $errorMessage */
            $errorMessage = isset($lastError['message']) ? $lastError['message'] : '';

            throw new ImportServiceException(
                __('Cannot create file with given source: %1', $errorMessage)
            );
        }

        /** set updated data to source */
        $source->setImportData($fileName)->setUuid($uuid)->setStatus(SourceInterface::STATUS_UPLOADED);

        /** save processed source with status */
        $source = $this->sourceRepository->save($source);

        return $source;
    }
}
