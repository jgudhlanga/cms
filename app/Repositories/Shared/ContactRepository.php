<?php

namespace App\Repositories\Shared;


use App\DTO\Shared\ContactDto;
use App\Models\Shared\Contact;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Shared\interface\IContactRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ContactRepository extends BaseRepository implements IContactRepository
{
    public function __construct(protected Contact $contact)
    {
        parent::__construct($this->contact);
    }

    public function create(Model $model, ContactDto $dto): Contact
    {
        $this->handleMainContact($model, $dto);
        return Contact::create(
            array_merge([
                'tenant_id' => $model->tenant_id ?? @Auth::user()->tenant_id,
                'contactable_id' => $model->id,
                'contactable_type' => get_class($model),
            ],
                $this->getFields($dto))
        );
    }

    public function update(Contact $contact, ContactDto $dto): Contact
    {
        $this->handleMainContact($contact, $dto);
        return tap($contact)->update($this->getFields($dto));
    }

    private function getFields(ContactDto $dto): array
    {
        return [
            'name' => $dto->name,
            'phone_number' => $dto->phone_number,
            'alt_phone_number' => $dto->alt_phone_number,
            'email_address' => $dto->email_address,
            'alt_email_address' => $dto->alt_email_address,
            'contact_is_main' => $dto->contact_is_main ?? false,
        ];
    }

    private function handleMainContact(Model $model, ContactDto $dto): void
    {
        if ($dto->contact_is_main) {
            Contact::query()
                ->where('contactable_type', get_class($model))
                ->where('contactable_id', $model->id)
                ->update(['contact_is_main' => false]);
        }
    }
}
