<?php

namespace App\Repositories\Shared\interface;

use App\DTO\Shared\ContactDto;
use App\Models\Shared\Contact;
use App\Repositories\Base\Interface\IBaseRepository;
use Illuminate\Database\Eloquent\Model;

interface IContactRepository extends IBaseRepository
{
	public function create(Model $model, ContactDto $dto);

	public function update(Contact $contact, ContactDto $dto);

}
