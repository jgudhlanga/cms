<?php

namespace App\Http\Controllers\Students;

use App\DTO\Students\UpdateStudentDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Students\UpdateStudentRequest;
use App\Models\Students\Student;
use App\Repositories\Students\interface\IStudentRepository;
use Illuminate\Http\Request;

class StudentController extends Controller
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


    public function show(string $id)
    {
        //
    }


    public function edit(string $id)
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
