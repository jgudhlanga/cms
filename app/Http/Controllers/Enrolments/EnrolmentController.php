<?php

namespace App\Http\Controllers\Enrolments;

use App\DTO\Students\UpdateStudentDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Students\UpdateStudentRequest;
use App\Models\Students\Student;
use App\Repositories\Students\interface\IStudentRepository;
use Illuminate\Http\Request;

class EnrolmentController extends Controller
{
    public function __construct(
        protected IStudentRepository $studentRepository,
    )
    {
    }

    public function index()
    {
        //
    }


    public function create()
    {
        //
    }


    public function store(Request $request)
    {
        //
    }


    public function show(Student $student)
    {
        //
    }


    public function edit(Student $student)
    {
        //
    }


    public function update(UpdateStudentRequest $request, Student $student)
    {
        $this->studentRepository->update($student, UpdateStudentDto::fromUpdateStudentRequest($request));
    }

    public function destroy(string $id)
    {
        //
    }
}
