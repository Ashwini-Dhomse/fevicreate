<?php

namespace App\Service;

use App\Repository\ActivitiesRepository;
use App\Transformer\ActivitiesTransformer;

class ActivitiesService
{
    public function __construct(
        private ActivitiesRepository $repository,
        private ActivitiesTransformer $transformer
    )
    {
    }

    public function getActivitiesList(int $page = 1,int $limit = 10) : array
    {

        $activities = $this->repository->getList();

        $data = [];

        foreach ($activities as $value) {

            $data[] = $this->transformer->list($value);
        }

        return $data;
    }

    public function getDetail(int $id)
    {
        $item = $this->repository->getById($id);

        if (!$item) {
            throw new \Exception('Not found');
        }

        return $this->transformer->detail($item);
    }
}