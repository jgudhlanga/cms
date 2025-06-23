<?php

namespace App\Providers;

use App\Repositories\Acl\Interface\IModuleRepository;
use App\Repositories\Acl\Interface\IPermissionRepository;
use App\Repositories\Acl\Interface\IRoleRepository;
use App\Repositories\Acl\ModuleRepository;
use App\Repositories\Acl\PermissionRepository;
use App\Repositories\Acl\RoleRepository;
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
use App\Repositories\Shared\AcademicLevelRepository;
use App\Repositories\Shared\AddressRepository;
use App\Repositories\Shared\AddressTypeRepository;
use App\Repositories\Shared\CommunicationMethodRepository;
use App\Repositories\Shared\ContactRepository;
use App\Repositories\Shared\CountryRepository;
use App\Repositories\Shared\DistrictRepository;
use App\Repositories\Shared\GenderRepository;
use App\Repositories\Shared\interface\IAcademicLevelRepository;
use App\Repositories\Shared\interface\IAddressRepository;
use App\Repositories\Shared\interface\IAddressTypeRepository;
use App\Repositories\Shared\interface\ICommunicationMethodRepository;
use App\Repositories\Shared\interface\IContactRepository;
use App\Repositories\Shared\interface\ICountryRepository;
use App\Repositories\Shared\interface\IDistrictRepository;
use App\Repositories\Shared\interface\IGenderRepository;
use App\Repositories\Shared\interface\ILanguageRepository;
use App\Repositories\Shared\interface\IMaritalStatusRepository;
use App\Repositories\Shared\interface\INextOfKinRepository;
use App\Repositories\Shared\interface\IPaymentDayRepository;
use App\Repositories\Shared\interface\IPaymentFrequencyRepository;
use App\Repositories\Shared\interface\IPaymentMethodRepository;
use App\Repositories\Shared\interface\IProvinceRepository;
use App\Repositories\Shared\interface\IRaceRepository;
use App\Repositories\Shared\interface\IRelationshipRepository;
use App\Repositories\Shared\interface\IReligionRepository;
use App\Repositories\Shared\interface\ISponsorTypeRepository;
use App\Repositories\Shared\interface\IStatusRepository;
use App\Repositories\Shared\interface\ITitleRepository;
use App\Repositories\Shared\LanguageRepository;
use App\Repositories\Shared\MaritalStatusRepository;
use App\Repositories\Shared\NextOfKinRepository;
use App\Repositories\Shared\PaymentDayRepository;
use App\Repositories\Shared\PaymentFrequencyRepository;
use App\Repositories\Shared\PaymentMethodRepository;
use App\Repositories\Shared\ProvinceRepository;
use App\Repositories\Shared\RaceRepository;
use App\Repositories\Shared\RelationshipRepository;
use App\Repositories\Shared\ReligionRepository;
use App\Repositories\Shared\SponsorTypeRepository;
use App\Repositories\Shared\StatusRepository;
use App\Repositories\Shared\TitleRepository;
use App\Repositories\Students\interface\IStudentProgramRepository;
use App\Repositories\Students\interface\IStudentRepository;
use App\Repositories\Students\StudentProgramRepository;
use App\Repositories\Students\StudentRepository;
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
        $this->app->bind(IAcademicLevelRepository::class, AcademicLevelRepository::class);
        $this->app->bind(ISponsorTypeRepository::class, SponsorTypeRepository::class);
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
