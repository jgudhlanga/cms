<?php

namespace App\JsonApi\V1;

use App\JsonApi\V1\AcademicCalendars\CourseWorkMarks\CourseWorkMarkSchema;
use App\JsonApi\V1\HMS\HmsSettings\HmsSettingSchema;
use App\JsonApi\V1\HMS\HostelApplications\HostelApplicationSchema;
use App\JsonApi\V1\HMS\HostelLeaves\HostelLeaveSchema;
use App\JsonApi\V1\HMS\HostelNotices\HostelNoticeSchema;
use App\JsonApi\V1\HMS\HostelQueries\HostelQuerySchema;
use App\JsonApi\V1\HMS\HostelRoomAllocations\HostelRoomAllocationSchema;
use App\JsonApi\V1\HMS\HostelRooms\HostelRoomSchema;
use App\JsonApi\V1\HMS\Hostels\HostelSchema;
use App\JsonApi\V1\Students\StudentApplications\StudentApplicationSchema;
use LaravelJsonApi\Core\Server\Server as BaseServer;

class Server extends BaseServer
{
    protected string $baseUri = '/api/v1/json';

    public function serving(): void
    {
        // Tenant scoping is applied via BelongsToTenant global scope on HMS models.
    }

    protected function allSchemas(): array
    {
        return [
            HostelSchema::class,
            HostelRoomSchema::class,
            HostelRoomAllocationSchema::class,
            HostelApplicationSchema::class,
            HostelQuerySchema::class,
            HostelLeaveSchema::class,
            HostelNoticeSchema::class,
            HmsSettingSchema::class,
            StudentApplicationSchema::class,
            CourseWorkMarkSchema::class,
        ];
    }
}
