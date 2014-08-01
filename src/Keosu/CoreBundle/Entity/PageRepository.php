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
namespace Keosu\CoreBundle\Entity;
use Doctrine\ORM\EntityRepository;

/**
 * PageRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class PageRepository extends EntityRepository {
	
	/***
	 * Find all shared gadget in a zone for an app
	 */
	public function countIsMainByAppId($appid ) {
		$qb = $this->_em->createQueryBuilder();
		$qb->select('count(a.id)');
		$qb->from('Keosu\CoreBundle\Entity\Page', 'a');
		$qb->where('a.isMain=1');
		$qb->andWhere('a.appId=?1');
		$qb->setParameter(1, $appid);
		$query = $qb->getQuery();
		$nbr=$query->getSingleScalarResult();
		return $nbr;
	
	}
}

