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
namespace Keosu\CoreBundle\Controller;

use Keosu\CoreBundle\Entity\Gadget;

use Keosu\CoreBundle\Entity\Page;

use Keosu\CoreBundle\Util\TemplateUtil;
use Keosu\CoreBundle\Util\StringUtil;
use Keosu\Gadget\MenuGadgetBundle\Form\MenuPageType;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Common class for editing gadget
 * This class will be wall by specific gadget controller
 * @author vleborgne
 *
 */
class GadgetEditController extends Controller {

	/**
	 * Clean a zone and delete all the gadgets inside
	 * @param  $page where we want to delete the gadget
	 * @param $zone where we want to delete the gadget
	 */
	public function deleteAction($page, $zone) {
		$appid = $this->container->get('keosu_core.curapp')->getCurApp();
		$em = $this->get('doctrine')->getManager();

		$pageObject = $em->getRepository('KeosuCoreBundle:Page')->find($page);

		// We don't allow to delete authenticationGadget on private app
		if($pageObject->getTemplateId() != TemplateUtil::getAuthenticationTemplateId() &&
				$pageObject->getName() != TemplateUtil::getAuthenticationPageName()) {
			//Look if there is a shared gadget in this zone
			$commonGadget=$em->getRepository('KeosuCoreBundle:Gadget')->findSharedByZoneAndApp($zone,$appid);
			//If there is no share gadget we try to find the specific one
			if($commonGadget == null){
				$commonGadget = $em->getRepository('KeosuCoreBundle:Gadget')
					->findOneBy(array('zone' => $zone, 'page' => $page));
			}
			//Delete the gadget
			$em->remove($commonGadget);
			$em->flush();

			//Export app
			$this->container->get('keosu_core.exporter')->exportApp();
		}

		//Redirect to the last page
		return $this->redirect(
						$this->generateUrl('keosu_core_views_page',array(
												'id' => $page))
							);
	}
	/**
	 * Ading a new Gadget in a zone
	 * @param $page where we want to add the gadget
	 * @param $zone where we want to add the gadget
	 * @param $gadget name of the gadget
	 */
	public function addAction($page, $zone, $gadget) {

		$em = $this->get('doctrine')->getManager();
		$pageObject = $em->getRepository('KeosuCoreBundle:Page')->find($page);

		// We don't allow to delete authenticationGadget on private app
		if($pageObject->getTemplateId() != TemplateUtil::getAuthenticationTemplateId() &&
				$pageObject->getName() != TemplateUtil::getAuthenticationPageName()) {

			$gadget = $this->addGadgetAction($page, $zone,$gadget);
			return $this->formGadget($gadget);//Create form
		}

		//Redirect to the last page
		return $this->redirect(
						$this->generateUrl('keosu_core_views_page',array(
												'id' => $page))
							);
	}
	/**
	 * Adding gadget process
	 */
	private function addGadgetAction($page, $zone, $gadgetName) {

		$em = $this->get('doctrine')->getManager();

		// search if there is allready a gadget
		$gadget = $em->getRepository('KeosuCoreBundle:Gadget')->findOneBy(array(
																				'zone' => $zone,
																				'page' => $page)
																			);

		if($gadget == null) {
			$gadget = new Gadget();
			$pageObject = $em->getRepository('KeosuCoreBundle:Page')->find($page);
			$gadget->setPage($pageObject);
			$gadget->setZone($zone);
		}

		$gadget->setName($gadgetName);
		return $gadget;
	}

	/**
	 * Edit an existing gadget
	 * Same process as Add
	 */
	public function editAction($page, $zone) {
		//Call the common gadget function with gadget class in parameter
		$gadgetArray = $this::editGadgetCommonAction($page, $zone,$this->getGadgetClass());
		$specificGadget = $gadgetArray['specific'];
		$commonGadget = $gadgetArray['common'];
		return $this->formGadget($specificGadget, $commonGadget, null);
	}

	/**
	 * Editing gadget process
	 */
	private function editGadgetCommonAction($page, $zone, $gadgetClass) {
		$appid = $this->container->get('keosu_core.curapp')->getCurApp();
		$em = $this->get('doctrine')->getManager();
		//Look if there is a shared gadget in this zone
		$commonGadget = $em->getRepository('KeosuCoreBundle:Gadget')->findSharedByZoneAndApp($zone,$appid);
		//If there is no share gadget we try to find the specific one
		if($commonGadget == null){
			$commonGadget = $em->getRepository('KeosuCoreBundle:Gadget')
				->findOneBy(array('zone' => $zone, 'page' => $page));
		}

		//Convert the common gadget to a specific gadget object
		$gadget = $gadgetClass::constructfromGadget($commonGadget);
		$gadgetArray = Array();
		$gadgetArray['specific'] = $gadget;
		$gadgetArray['common'] = $commonGadget;
		return $gadgetArray;
	}

	private function getTemplateList($gadgetName) {
		$ret = array();
		$pathTemplate = $this->get('keosu_core.packagemanager')->getpath($gadgetName);
		$templates = scandir($pathTemplate);

		foreach($templates as $t)
			if(StringUtil::endsWith($t, ".html"))
				$ret[$t] = $t;

		return $ret;
	}

	/**
	 * Create the form to edit/add the gadget
	 */
	private function formGadget($gadget) {
		$formBuilder = $this->createFormBuilder($gadget);

		$formBuilder->add('template', 'choice',array(
							'choices'  => $this->getTemplateList($gadget->getName()),
							'required' => true,
							'expanded' => true))
					->add('shared', 'checkbox', array(
							'label'     => 'Shared with all pages',
							'required'  => false,));

		//Build gadget form is defined in child class (the specific controller one)
		$this->buildGadgetForm($formBuilder, $gadget->getName());
		$form = $formBuilder->getForm();
		return $this->formCommonGadget($form,$gadget);
	}

	//Specific form for the gadget
	public function buildGadgetForm($formBuilder, $gadgetName) {
		$appid = $this->container->get('keosu_core.curapp')->getCurApp();


		$em = $this->get('doctrine')->getManager();
		$pages = $em->getRepository('KeosuCoreBundle:Page')->findByAppId($appid);

		$pageList = Array();
		foreach ($pages as $page) {
			$pageList[$page->getId()] = $page->getName();
		}

		$formBuilder->add('config', 'collection',array(
						'type'         => new MenuPageType($pageList),
						'required'     => false,
						'label'        => false,
						'allow_add'    => true,
						'allow_delete' => true,
						'by_reference' => true,
						'options'      => array(
							'label'  => false,
						)
				));
	}

	/**
	 * Form submit process
	 * Store the gadget in database
	 */
	public function formCommonGadget($form, $gadget) {
		$em = $this->get('doctrine')->getManager();
		$request = $this->get('request');
		//If we are in POST method, form is submit
		if ($request->getMethod() == 'POST') {
			$form->bind($request);
			if ($form->isValid()) {

				$em->persist($commonGadget);
				$em->flush();

				$this->container->get('keosu_core.exporter')->exportApp();

				return $this->redirect($this->generateUrl('keosu_core_views_page',array(
													'id' => $idPage)
									));
			}
		}
		return $this->render($this->getRenderEditTemplate(), array(
								'form'      => $form->createView(),
								'gadgetDir' => $this->getTemplateList($gadget->getName())
							));
	}

	/**
	 * Can be overrided
	 */
	public function getRenderEditTemplate(){
		return 'KeosuCoreBundle:Page:editGadget.html.twig';
	}

}
