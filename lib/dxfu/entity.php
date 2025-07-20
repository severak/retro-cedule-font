<?php
namespace dxfu;

/**
 * Interface for DXF entity.
 */
interface entity
{
    /**
     * Imports two lines of DXF data to this entity. Called repeatedly.
     *
     * @param int   $groupCode Group code. If 0 then it's last call of import to this entity.
     * @param mixed $value     Value of data.
     * @return void
     */
    public function importDXF($groupCode, $value);

    /**
     * Export DXF data of this entity as array of $groupCode and $value pairs.
     *
     * @return array[]
     */
    public function exportDXF();
}
