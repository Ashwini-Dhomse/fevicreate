<?php

namespace App\Service;

use App\DTO\ContestSubmissionDTO;
use App\Repository\ContestSubmissionRepository;
use App\Transformer\ContestSubmissionTransformer;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ContestSubmission;

class ContestSubmissionService
{
    private $repository;
    private $fileService;

    public function __construct(
        FileUploadService $fileService,
        ContestSubmissionRepository $repository,
    ) {
        $this->fileService = $fileService;
        $this->repository = $repository;
    }

    public function save(ContestSubmissionDTO $dto)
    {
        $object = new ContestSubmission();

        $date = date('M Y');

        $contentDirectory = DataObject\Service::createFolderByPath('/Contest Submissions/'. $date .'/');
        $contentDirectoryId = $contentDirectory->getId();

        $objectKey = 'contest_'.$dto->artworkTitle.'_'.rand(1, 999);
        $object->setKey($objectKey);
        $object->setParentId($contentDirectoryId);

        $object->setChildName($dto->childName);
        $object->setParentEmail($dto->parentEmail);
        $object->setParentPhone($dto->parentPhone);
        $object->setArtworkTitle($dto->artworkTitle);

        // Upload files
        $assets = [];

        $year = date('Y');
        $month = date('m');

        $folderPath = "/contest-submissions/$year/$month";

        foreach ($dto->documents as $file) {
            $assets[] = $this->fileService->upload($file, $folderPath, 'contest_');
        }

        $object->setDocuments($assets);

        $object->save();

        return $object->getId();
    }

    public function getList($page = 1, $limit = 10)
    {

        $list = $this->repository->getList($page, $limit);

        $data = [];

        foreach ($list as $item) {
            $data[] = ContestSubmissionTransformer::list($item);
        }

        return $data;
    }

    public function getDetail($id)
    {
        $item = $this->repository->getById($id);

        if (!$item) {
            throw new \Exception('Not found');
        }

        return ContestSubmissionTransformer::detail($item);
    }
}
