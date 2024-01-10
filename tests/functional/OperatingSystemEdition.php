<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

namespace tests\units;

require_once 'CommonDropdown.php';

/* Test for inc/operatingsystem.class.php */

class OperatingSystemEdition extends CommonDropdown
{
    public function getObjectClass()
    {
        return '\OperatingSystemEdition';
    }

    public function typenameProvider()
    {
        return [
            [\OperatingSystemEdition::getTypeName(), 'Editions'],
            [\OperatingSystemEdition::getTypeName(0), 'Editions'],
            [\OperatingSystemEdition::getTypeName(10), 'Editions'],
            [\OperatingSystemEdition::getTypeName(1), 'Edition']
        ];
    }

    public function testMaybeTranslated()
    {
        $this
         ->given($this->newTestedInstance)
            ->then
               ->boolean($this->testedInstance->maybeTranslated())->isTrue();
    }

    protected function getTabs()
    {
        return [
            'OperatingSystemEdition$main' => "<span><i class='ti ti-edit me-2'></i>Edition</span>",
        ];
    }

    /**
     * Create new Operating system in database
     *
     * @return void
     */
    protected function newInstance()
    {
        $this->newTestedInstance();
        $this->integer(
            (int)$this->testedInstance->add([
                'name' => 'OS name ' . $this->getUniqueString()
            ])
        )->isGreaterThan(0);
        $this->boolean($this->testedInstance->getFromDB($this->testedInstance->getID()))->isTrue();
    }
}
