<?php

namespace App\Repositories\Students;

use App\DTO\Students\AcademicRecordDto;
use App\Models\Students\AcademicRecord;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Students\interface\IAcademicRecordRepository;

class AcademicRecordRepository extends BaseRepository implements IAcademicRecordRepository
{

    public function __construct(
        protected AcademicRecord $academicRecord,
    )
    {
        parent::__construct($this->academicRecord);
    }

    public function create(AcademicRecordDto $dto)
    {
        return $this->academicRecord->create($this->getFields($dto))->refresh();
    }

    public function update(AcademicRecord $academicRecord, AcademicRecordDto $dto)
    {
        return tap($academicRecord)->update($this->getFields($dto));
    }

    public function allFilter($columns = ['*'])
    {
        return $this->academicRecord
            ->select($columns)
            ->orderBy('created_at')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }

    private function getFields(AcademicRecordDto $dto): array
    {
        return [
            'student_id' => $dto->id,
            'school' => $dto->school,
            'place' => $dto->place,
            'from_level' => $dto->from_level,
            'to_level' => $dto->to_level,
            'from_year' => $dto->from_year,
            'to_year' => $dto->to_year,
            'student_unique_number' => $dto->student_unique_number,
            'exam_board' => $dto->exam_board,
            'exam_month' => $dto->exam_month,
            'exam_year' => $dto->exam_year,
            'exam_center' => $dto->exam_center,
            'exam_results' => $dto->exam_results,
        ];
    }
}
