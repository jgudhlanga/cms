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
use App\Repositories\Districts\DistrictRepository;
use App\Repositories\Districts\interface\IDistrictRepository;
use App\Repositories\Genders\GenderRepository;
use App\Repositories\Genders\interface\IGenderRepository;
use App\Repositories\Institution\CourseRepository;
use App\Repositories\Institution\DepartmentCourseRepository;
use App\Repositories\Institution\DepartmentLevelRepository;
use App\Repositories\Institution\DepartmentRepository;
use App\Repositories\Institution\DivisionRepository;
use App\Repositories\Institution\GradeRepository;
use App\Repositories\Institution\InstitutionDepartmentRepository;
use App\Repositories\Institution\interface\ICourseRepository;
use App\Repositories\Institution\interface\IDepartmentCourseRepository;
use App\Repositories\Institution\interface\IDepartmentLevelRepository;
use App\Repositories\Institution\interface\IDepartmentRepository;
use App\Repositories\Institution\interface\IDivisionRepository;
use App\Repositories\Institution\interface\IGradeRepository;
use App\Repositories\Institution\interface\IInstitutionDepartmentRepository;
use App\Repositories\Institution\interface\ILevelRepository;
use App\Repositories\Institution\interface\IModeOfStudyRepository;
use App\Repositories\Institution\interface\ISubjectRepository;
use App\Repositories\Institution\LevelRepository;
use App\Repositories\Institution\ModeOfStudyRepository;
use App\Repositories\Institution\SubjectRepository;
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
use App\Repositories\Relationships\interface\IRelationshipRepository;
use App\Repositories\Relationships\RelationshipRepository;
use App\Repositories\Religions\interface\IReligionRepository;
use App\Repositories\Religions\ReligionRepository;
use App\Repositories\Shared\AddressRepository;
use App\Repositories\Shared\ContactRepository;
use App\Repositories\Shared\interface\IAddressRepository;
use App\Repositories\Shared\interface\IContactRepository;
use App\Repositories\Shared\interface\INextOfKinRepository;
use App\Repositories\Shared\NextOfKinRepository;
use App\Repositories\Statuses\interface\IMaritalStatusRepository;
use App\Repositories\Statuses\interface\IStatusRepository;
use App\Repositories\Statuses\MaritalStatusRepository;
use App\Repositories\Statuses\StatusRepository;
use App\Repositories\Students\interface\IStudentProgramRepository;
use App\Repositories\Students\interface\IStudentRepository;
use App\Repositories\Students\StudentProgramRepository;
use App\Repositories\Students\StudentRepository;
use App\Repositories\Titles\interface\ITitleRepository;
use App\Repositories\Titles\TitleRepository;
use App\Repositories\Users\interface\IUserRepository;
use App\Repositories\Users\UserRepository;
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
        $this->institutionRepositories();
        $this->userRepositories();
        $this->studentRepositories();
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
        $this->app->bind(IMaritalStatusRepository::class, MaritalStatusRepository::class);
        $this->app->bind(ITitleRepository::class, TitleRepository::class);
        $this->app->bind(IAddressTypeRepository::class, AddressTypeRepository::class);
        $this->app->bind(IRelationshipRepository::class, RelationshipRepository::class);
        $this->app->bind(IDistrictRepository::class, DistrictRepository::class);
        $this->app->bind(IReligionRepository::class, ReligionRepository::class);
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
        $this->app->bind(IContactRepository::class, ContactRepository::class);
        $this->app->bind(INextOfKinRepository::class, NextOfKinRepository::class);
    }

    public function institutionRepositories(): void
    {
        $this->app->bind(ICourseRepository::class, CourseRepository::class);
        $this->app->bind(IDepartmentRepository::class, DepartmentRepository::class);
        $this->app->bind(IDivisionRepository::class, DivisionRepository::class);
        $this->app->bind(IGradeRepository::class, GradeRepository::class);
        $this->app->bind(ILevelRepository::class, LevelRepository::class);
        $this->app->bind(IModeOfStudyRepository::class, ModeOfStudyRepository::class);
        $this->app->bind(ISubjectRepository::class, SubjectRepository::class);
        $this->app->bind(IInstitutionDepartmentRepository::class, InstitutionDepartmentRepository::class);
        $this->app->bind(IDepartmentLevelRepository::class, DepartmentLevelRepository::class);
        $this->app->bind(IDepartmentCourseRepository::class, DepartmentCourseRepository::class);
    }

    public function userRepositories(): void
    {
        $this->app->bind(IUserRepository::class, UserRepository::class);
    }

    public function studentRepositories(): void
    {
        $this->app->bind(IStudentRepository::class, StudentRepository::class);
        $this->app->bind(IStudentProgramRepository::class, StudentProgramRepository::class);
    }

}
