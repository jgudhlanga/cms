<?php

namespace App\Http\Controllers\Shared;

use App\DTO\Shared\ContactDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shared\ContactRequest;
use App\Models\Shared\Contact;
use App\Repositories\Shared\interface\IContactRepository;
use Illuminate\Http\Request;

class ContactController extends Controller
{
	public function __construct(protected IContactRepository $repository)
	{
	}


	public function update(ContactRequest $request, Contact $contact)
	{
		$this->authorize('update', $contact);
		$this->repository->update($contact, ContactDto::fromContactRequest($request));
	}

	public function destroy(Contact $contact)
	{
		$this->authorize('delete', $contact);
		$this->repository->delete($contact);
	}

	public function restore(string $id)
	{
		$contact = $this->repository->findTrashed($id);
		$this->authorize('restore', $contact);
		$this->repository->restore($contact);
	}

	public function forceDelete(Contact $contact)
	{
		$this->authorize('forceDelete', $contact);
		$this->repository->delete($contact, true);
	}
}
