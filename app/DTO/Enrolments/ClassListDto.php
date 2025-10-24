<?php

namespace App\DTO\Enrolments;


readonly class ClassListDto
{
	public function __construct(
		public int  $student_program_id,
		public string $type,
        public array $attributes,
	)
	{
	}
}
