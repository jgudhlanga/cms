<?php

namespace App\Providers;

use App\Repositories\Acl\Interface\IModuleRepository;
use App\Repositories\Acl\Interface\IPermissionRepository;
use App\Repositories\Acl\Interface\IRoleRepository;
use App\Repositories\Acl\ModuleRepository;
use App\Repositories\Acl\PermissionRepository;
use App\Repositories\Acl\RoleRepository;
use App\Repositories\AddressTypes\AddressTypeRepository;
use App\Repositories\AddressTypes\interface\IAddressTypeRepository;
use App\Repositories\Communications\CommunicationMethodRepository;
use App\Repositories\Communications\interface\ICommunicationMethodRepository;
use App\Repositories\Countries\CountryRepository;
use App\Repositories\Countries\interface\ICountryRepository;
use App\Repositories\Genders\GenderRepository;
use App\Repositories\Genders\interface\IGenderRepository;
use App\Repositories\Languages\interface\ILanguageRepository;
use App\Repositories\Languages\LanguageRepository;
use App\Repositories\Payments\interface\IPaymentDayRepository;
use App\Repositories\Payments\interface\IPaymentFrequencyRepository;
use App\Repositories\Payments\interface\IPaymentMethodRepository;
use App\Repositories\Payments\PaymentDayRepository;
use App\Repositories\Payments\PaymentFrequencyRepository;
use App\Repositories\Payments\PaymentMethodRepository;
use App\Repositories\Provinces\interface\IProvinceRepository;
use App\Repositories\Provinces\ProvinceRepository;
use App\Repositories\Races\interface\IRaceRepository;
use App\Repositories\Races\RaceRepository;
use App\Repositories\Shared\AddressRepository;
use App\Repositories\Shared\BankDetailRepository;
use App\Repositories\Shared\ContactRepository;
use App\Repositories\Shared\interface\IAddressRepository;
use App\Repositories\Shared\interface\IBankDetailRepository;
use App\Repositories\Shared\interface\IContactRepository;
use App\Repositories\Statuses\interface\IStatusRepository;
use App\Repositories\Statuses\StatusRepository;
use App\Repositories\Titles\interface\ITitleRepository;
use App\Repositories\Titles\TitleRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function __construct($app)
    {
        parent::__construct($app);
    }

    public function register(): void
    {
        $this->aclRepositories();
        $this->dropdownRepositories();
        $this->paymentsRepositories();
        $this->sharedRepositories();
    }

    public function boot(): void
    {
        #
    }

    private function aclRepositories(): void
    {
        $this->app->bind(IModuleRepository::class, ModuleRepository::class);
        $this->app->bind(IRoleRepository::class, RoleRepository::class);
        $this->app->bind(IPermissionRepository::class, PermissionRepository::class);
    }

    private function dropdownRepositories(): void
    {
        $this->app->bind(ICommunicationMethodRepository::class, CommunicationMethodRepository::class);
        $this->app->bind(ICountryRepository::class, CountryRepository::class);
        $this->app->bind(IGenderRepository::class, GenderRepository::class);
        $this->app->bind(ILanguageRepository::class, LanguageRepository::class);
        $this->app->bind(IProvinceRepository::class, ProvinceRepository::class);
        $this->app->bind(IRaceRepository::class, RaceRepository::class);
        $this->app->bind(IStatusRepository::class, StatusRepository::class);
        $this->app->bind(ITitleRepository::class, TitleRepository::class);
        $this->app->bind(IAddressTypeRepository::class, AddressTypeRepository::class);
    }

    private function paymentsRepositories(): void
    {
        $this->app->bind(IPaymentDayRepository::class, PaymentDayRepository::class);
        $this->app->bind(IPaymentFrequencyRepository::class, PaymentFrequencyRepository::class);
        $this->app->bind(IPaymentMethodRepository::class, PaymentMethodRepository::class);
    }

    public function sharedRepositories(): void
    {
        $this->app->bind(IAddressRepository::class, AddressRepository::class);
        $this->app->bind(IBankDetailRepository::class, BankDetailRepository::class);
        $this->app->bind(IContactRepository::class, ContactRepository::class);
    }
}
