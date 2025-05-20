<?php

namespace Leantime\Plugins\ShowTicket\Repositories;

use Leantime\Core\Db\Repository;

class ShowTicket extends Repository
{
    /**
     * Get database connection
     */
    public function __construct()
    {
    }

    /**
     * @param int $id the ticket id
 * @return array<int, mixed>
 */
    public function getTimesheetsByTicket(int $id): array
    {
        $query = "SELECT
        userid,
                zp_timesheets.workdate,
                DATE_FORMAT(zp_timesheets.workDate, '%Y-%m-%d') AS utc,
                hours as loggedHours
            FROM
                zp_timesheets
            WHERE
                zp_timesheets.ticketId = :ticketId
                AND workDate <> '0000-00-00 00:00:00' AND workDate <> '1969-12-31 00:00:00'
            ORDER BY utc";

        $call = $this->dbcall(func_get_args());

        $call->prepare($query);
        $call->bindValue(':ticketId', $id);

        $values = $call->fetchAll();

        return $values;
    }
}
