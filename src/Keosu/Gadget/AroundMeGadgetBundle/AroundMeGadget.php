<?php
/************************************************************************
 Keosu is an open source CMS for mobile app
Copyright (C) 2014  Vincent Le Borgne, Pockeit

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as
published by the Free Software Foundation, either version 3 of the
License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
************************************************************************/
namespace Keosu\Gadget\AroundMeGadgetBundle;
use Keosu\CoreBundle\iGadget;
use Keosu\CoreBundle\GadgetParent;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class AroundMeGadget extends GadgetParent implements iGadget {
	/**
	 * Gadget Name
	 * Used to find the gadget template path and routs
	 */
	public function getGadgetName() {
		return "aroundme_gadget";
	}
	
	public function isStatic() {
		return false;
	}
	
	/**
	 * Construct a specific Gadget from a common Gadget (Keosu\CoreBundle\Entity\Gadget)
	 */
	public static function constructFromGadget($gadget){
		$instance = new self();
		parent::constructParentFromGadget($gadget,$instance);
		return $instance;
		
	}
	
	public function getRequieredPermissions() {
		$ret = array();
		$ret[] = $this::PERMISSION_GOOGLE_MAP_API;
		$ret[] = $this::PERMISSION_GEOLOCATION;
		return $ret;
	}
	public function getExtraJsToImport() {
		return array("https://maps.googleapis.com/maps/api/js?sensor=false");
	}

}
