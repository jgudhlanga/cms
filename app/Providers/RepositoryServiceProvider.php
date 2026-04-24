<?php

namespace App\Providers;

use App\Repositories\AcademicCalendars\AcademicYearOptionRepository;
use App\Repositories\AcademicCalendars\interface\IAcademicYearOptionRepository;
use App\Repositories\Acl\Interface\IModuleRepository;
use App\Repositories\Acl\Interface\IPermissionRepository;
use App\Repositories\Acl\Interface\IRoleGroupRepository;
use App\Repositories\Acl\Interface\IRoleRepository;
use App\Repositories\Acl\ModuleRepository;
use App\Repositories\Acl\PermissionRepository;
use App\Repositories\Acl\RoleGroupRepository;
use App\Repositories\Acl\RoleRepository;
use App\Repositories\Finance\FinanceExchangeRateRepository;
use App\Repositories\Finance\interface\IFinanceExchangeRateRepository;
use App\Repositories\Institution\AssessmentTypeRepository;
use App\Repositories\Institution\ClassListRepository;
use App\Repositories\Institution\CourseRepository;
use App\Repositories\Institution\CourseSyllabusRepository;
use App\Repositories\Institution\DepartmentApplicationStepRepository;
use App\Repositories\Institution\DepartmentCourseRepository;
use App\Repositories\Institution\DepartmentLevelRepository;
use App\Repositories\Institution\DepartmentRepository;
use App\Repositories\Institution\DivisionRepository;
use App\Repositories\Institution\DocumentTemplateRepository;
use App\Repositories\Institution\FeeStructureRepository;
use App\Repositories\Institution\GradeRepository;
use App\Repositories\Institution\InstitutionDepartmentRepository;
use App\Repositories\Institution\IntakePeriodRepository;
use App\Repositories\Institution\interface\IAssessmentTypeRepository;
use App\Repositories\Institution\interface\IClassListRepository;
use App\Repositories\Institution\interface\ICourseRepository;
use App\Repositories\Institution\interface\ICourseSyllabusRepository;
use App\Repositories\Institution\interface\IDepartmentApplicationStepRepository;
use App\Repositories\Institution\interface\IDepartmentCourseRepository;
use App\Repositories\Institution\interface\IDepartmentLevelRepository;
use App\Repositories\Institution\interface\IDepartmentRepository;
use App\Repositories\Institution\interface\IDivisionRepository;
use App\Repositories\Institution\interface\IDocumentTemplateRepository;
use App\Repositories\Institution\interface\IFeeStructureRepository;
use App\Repositories\Institution\interface\IGradeRepository;
use App\Repositories\Institution\interface\IInstitutionDepartmentRepository;
use App\Repositories\Institution\interface\IIntakePeriodRepository;
use App\Repositories\Institution\interface\ILevelRepository;
use App\Repositories\Institution\interface\IModeOfStudyRepository;
use App\Repositories\Institution\interface\IStaffRepository;
use App\Repositories\Institution\interface\ISubjectRepository;
use App\Repositories\Institution\interface\ISyllabusCourseModuleRepository;
use App\Repositories\Institution\LevelRepository;
use App\Repositories\Institution\ModeOfStudyRepository;
use App\Repositories\Institution\StaffRepository;
use App\Repositories\Institution\SubjectRepository;
use App\Repositories\Institution\SyllabusCourseModuleRepository;
use App\Repositories\Shared\AcademicLevelRepository;
use App\Repositories\Shared\AddressRepository;
use App\Repositories\Shared\AddressTypeRepository;
use App\Repositories\Shared\CommunicationMethodRepository;
use App\Repositories\Shared\ContactRepository;
use App\Repositories\Shared\CountryRepository;
use App\Repositories\Shared\DistrictRepository;
use App\Repositories\Shared\DocumentTypeRepository;
use App\Repositories\Shared\EmploymentTypeRepository;
use App\Repositories\Shared\FeeTypeRepository;
use App\Repositories\Shared\GenderRepository;
use App\Repositories\Shared\IdTypeRepository;
use App\Repositories\Shared\interface\IAcademicLevelRepository;
use App\Repositories\Shared\interface\IAddressRepository;
use App\Repositories\Shared\interface\IAddressTypeRepository;
use App\Repositories\Shared\interface\ICommunicationMethodRepository;
use App\Repositories\Shared\interface\IContactRepository;
use App\Repositories\Shared\interface\ICountryRepository;
use App\Repositories\Shared\interface\IDistrictRepository;
use App\Repositories\Shared\interface\IDocumentTypeRepository;
use App\Repositories\Shared\interface\IEmploymentTypeRepository;
use App\Repositories\Shared\interface\IFeeTypeRepository;
use App\Repositories\Shared\interface\IGenderRepository;
use App\Repositories\Shared\interface\IIdTypeRepository;
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
use App\Repositories\Shared\interface\IWorkflowStepActionRepository;
use App\Repositories\Shared\interface\IWorkflowStepRepository;
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
use App\Repositories\Shared\WorkflowStepActionRepository;
use App\Repositories\Shared\WorkflowStepRepository;
use App\Repositories\Students\AcademicRecordRepository;
use App\Repositories\Students\interface\IAcademicRecordRepository;
use App\Repositories\Students\interface\ISponsorRepository;
use App\Repositories\Students\interface\IStudentEnrolmentStatusRepository;
use App\Repositories\Students\interface\IStudentProgramRepository;
use App\Repositories\Students\interface\IStudentRepository;
use App\Repositories\Students\SponsorRepository;
use App\Repositories\Students\StudentEnrolmentStatusRepository;
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
        $this->financeRepositories();
        $this->sharedRepositories();
        $this->institutionRepositories();
        $this->userRepositories();
        $this->studentRepositories();
        $this->academicCalendarRepositories();
    }

    public function boot(): void
    {
        //
    }

    private function aclRepositories(): void
    {
        $this->app->bind(IModuleRepository::class, ModuleRepository::class);
        $this->app->bind(IRoleGroupRepository::class, RoleGroupRepository::class);
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
        $this->app->bind(IWorkflowStepRepository::class, WorkflowStepRepository::class);
        $this->app->bind(IWorkflowStepActionRepository::class, WorkflowStepActionRepository::class);
        $this->app->bind(IEmploymentTypeRepository::class, EmploymentTypeRepository::class);
        $this->app->bind(IIdTypeRepository::class, IdTypeRepository::class);
        $this->app->bind(IDocumentTypeRepository::class, DocumentTypeRepository::class);
        $this->app->bind(IFeeTypeRepository::class, FeeTypeRepository::class);
    }

    private function paymentsRepositories(): void
    {
        $this->app->bind(IPaymentDayRepository::class, PaymentDayRepository::class);
        $this->app->bind(IPaymentFrequencyRepository::class, PaymentFrequencyRepository::class);
        $this->app->bind(IPaymentMethodRepository::class, PaymentMethodRepository::class);
    }

    private function financeRepositories(): void
    {
        $this->app->bind(IFinanceExchangeRateRepository::class, FinanceExchangeRateRepository::class);
    }

    public function sharedRepositories(): void
    {
        $this->app->bind(IAddressRepository::class, AddressRepository::class);
        $this->app->bind(IContactRepository::class, ContactRepository::class);
        $this->app->bind(INextOfKinRepository::class, NextOfKinRepository::class);
    }

    public function institutionRepositories(): void
    {
        $this->app->bind(IAssessmentTypeRepository::class, AssessmentTypeRepository::class);
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
        $this->app->bind(IDepartmentApplicationStepRepository::class, DepartmentApplicationStepRepository::class);
        $this->app->bind(IIntakePeriodRepository::class, IntakePeriodRepository::class);
        $this->app->bind(IStaffRepository::class, StaffRepository::class);
        $this->app->bind(IDocumentTemplateRepository::class, DocumentTemplateRepository::class);
        $this->app->bind(IFeeStructureRepository::class, FeeStructureRepository::class);
        $this->app->bind(IClassListRepository::class, ClassListRepository::class);
        $this->app->bind(ICourseSyllabusRepository::class, CourseSyllabusRepository::class);
        $this->app->bind(ISyllabusCourseModuleRepository::class, SyllabusCourseModuleRepository::class);
    }

    public function userRepositories(): void
    {
        $this->app->bind(IUserRepository::class, UserRepository::class);
    }

    public function studentRepositories(): void
    {
        $this->app->bind(IStudentRepository::class, StudentRepository::class);
        $this->app->bind(IStudentProgramRepository::class, StudentProgramRepository::class);
        $this->app->bind(ISponsorRepository::class, SponsorRepository::class);
        $this->app->bind(IAcademicRecordRepository::class, AcademicRecordRepository::class);
        $this->app->bind(IStudentEnrolmentStatusRepository::class, StudentEnrolmentStatusRepository::class);
    }

    public function academicCalendarRepositories(): void
    {
        $this->app->bind(IAcademicYearOptionRepository::class, AcademicYearOptionRepository::class);
    }
}
