<?php

/**
* @file plugins/generic/vgWort/VGWortPlugin.inc.php
*
* Copyright (c) 2018 Center for Digital Systems (CeDiS), Freie Universität Berlin
* Distributed under the GNU GPL v2. For full terms see the file LICENSE.
*
* @class VGWortPlugin
* @ingroup plugins_generic_vgWort
*
* @brief VG Wort plugin class.
*/

import('lib.pkp.classes.plugins.GenericPlugin');
import('lib.pkp.classes.submission.SubmissionFile');
import('lib.pkp.classes.components.forms.FieldOptions');

define('NOTIFICATION_TYPE_VGWORT_ERROR',0x400000A);

class VGWortPlugin extends GenericPlugin {

    public $pixelTagStatusLabels;

    /**
     * @copydoc Plugin::register()
     */
    function register($category, $path, $mainContextId = null) {
        if (parent::register($category, $path, $mainContextId)) {
            if ($this->getEnabled($mainContextId)) {
                $this->import('classes.form.VGWortForm');
                $this->import('classes.PixelTag');
                $this->import('classes.PixelTagDAO');
                $pixelTagDao = new PixelTagDAO($this->getName());
                $returner = DAORegistry::registerDAO('PixelTagDAO', $pixelTagDao);

                // Consider the new field vgWortCarNo in user and author metadata, and in user public profile
                // form template
                HookRegistry::register('Common::UserDetails::AdditionalItems', array($this, 'metadataFieldEdit'));
                HookRegistry::register('User::PublicProfile::AdditionalItems', array($this, 'metadataFieldEdit'));

                // form class
                HookRegistry::register('authorform::initdata', array($this, 'metadataInitData'));
                HookRegistry::register('authorform::readuservars', array($this, 'metadataReadUserVars'));
                HookRegistry::register('authorform::execute', array($this, 'metadataExecute'));
                HookRegistry::register('authorform::Constructor', array($this, 'addCheck'));

                // Consider the new field/setting vgWortCarNo in the user and author DAO
                HookRegistry::register('userdao::getAdditionalFieldNames', array($this, 'addFieldName'));
                HookRegistry::register('authordao::getAdditionalFieldNames', array($this, 'addFieldName'));

                // Assign pixel tag
                HookRegistry::register('Publication::edit', array($this, 'pixelExecuteSubmission'));
                HookRegistry::register('Templates::Controllers::Tab::PubIds::Form::PublicIdentifiersForm', array($this, 'pixelEdit'));
                HookRegistry::register('publicidentifiersform::readuservars', array($this, 'pixelReadUserVars'));
                HookRegistry::register('publicidentifiersform::execute', array($this, 'pixelExecuteRepresentation'));
                HookRegistry::register('articlegalleydao::getAdditionalFieldNames', array($this, 'addPixelFieldName'));

                // pixel tag listing
                HookRegistry::register('LoadComponentHandler', array($this, 'setupGridHandler'));
                HookRegistry::register('Template::Settings::distribution', array($this, 'pixelTagTab'));

                // Hook for article galley view -- add the pixel tag
                HookRegistry::register('TemplateManager::display', array($this, 'handleTemplateDisplay'));
                HookRegistry::register('TemplateManager::fetch', array($this, 'handleTemplateFetch'));

                // Hook for error notifications for editors
                HookRegistry::register('NotificationManager::getNotificationMessage', array($this, 'getNotificationMessage'));

                // Add VG Wort field to form tab
                HookRegistry::register('Schema::get::publication', array($this, 'addToPublicationSchema'));
                HookRegistry::register('Template::Workflow::Publication', array($this, 'addVGWortFormTab'));

                // Add vgWortCardNo field to "Edit contributor" Form
                HookRegistry::register('Schema::get::author', array($this, 'addToAuthorSchema'));

                // Add VG Wort Pixel to heiViewer Article Galley page
                HookRegistry::register("Plugins::heiViewerGalley::ArticleGalley", array($this,'insertPixelTagGalleyPageHook'));
                HookRegistry::register('AcronPlugin::parseCronTab', array($this, 'callbackParseCronTab'));

                $this->pixelTagStatusLabels = [
                    0 => __('plugins.generic.vgWort.pixelTag.representation.notAssigned'),
                    PT_STATUS_REGISTERED_ACTIVE => __('plugin.generic.vgWort.pixelTag.status.registeredactive'),
                    PT_STATUS_UNREGISTERED_ACTIVE => __('plugin.generic.vgWort.pixelTag.status.unregisteredactive'),
                    PT_STATUS_REGISTERED_REMOVED => __('plugin.generic.vgWort.pixelTag.status.registeredremoved'),
                    PT_STATUS_UNREGISTERED_REMOVED => __('plugin.generic.vgWort.pixelTag.status.unregisteredremoved')
                ];
            }
            return true;
        }
        return false;
    }

    /**
     * @copydoc AcronPlugin::parseCronTab()
     */
    function callbackParseCronTab($hookName, $args) {
        $taskFilesPath =& $args[0];
        $taskFilesPath[] = $this->getPluginPath() . DIRECTORY_SEPARATOR . 'scheduledTasks.xml';
        return false;
    }

    /**
     * Check whether the requirements for this plugin are fullfilled
     * @return boolean
     */
    function requirementsFulfilled() {
        $isSoapExtension = in_array('soap', get_loaded_extensions());
        $isOpenSSL = in_array('openssl', get_loaded_extensions());
        $isCURL = function_exists('curl_init');
        return $isSoapExtension && $isOpenSSL && $isCURL;
    }

    /**
     * @copydoc Plugin::getDisplayName()
     */
    function getDisplayName() {
        return __('plugins.generic.vgWort.displayName');
    }

    /**
     * @copydoc Plugin::getDescription()
     */
    function getDescription() {
        if ($this->requirementsFulfilled()) {
            return __('plugins.generic.vgWort.description');
        }
        return __('plugins.generic.vgWort.descriptionDisabled');
    }

    /**
     * @copydoc Plugin::getTemplatePath()
     */
    function getTemplatePath($inCore = false) {
        $ojsVersion = Application::getApplication()->getCurrentVersion()->getVersionString();
        return parent::getTemplatePath();
    }

    /**
     * @copydoc Plugin::getInstallSchemaFile()
     */
    function getInstallSchemaFile() {
        return $this->getPluginPath() . '/xml/schema.xml';
    }

    /**
     * @copydoc Plugin::manage()
     */
    function manage($args, $request) {
        $this->import('classes.form.VGWortSettingsForm');
        switch($request->getUserVar('verb')) {
            case 'settings':
                $settingsForm = new VGWortSettingsForm($this, $request->getContext()->getId());
                $settingsForm->initData($request);
                return new JSONMessage(true, $settingsForm->fetch($request));
            case 'save':
                $settingsForm = new VGWortSettingsForm($this, $request->getContext()->getId());
                $settingsForm->readInputData();
                if ($settingsForm->validate()) {
                    $settingsForm->execute();
                    $notificationManager = new NotificationManager();
                    $notificationManager->createTrivialNotification(
                        $request->getUser()->getId(),
                        NOTIFICATION_TYPE_SUCCESS
                    );
                    return new JSONMessage(true);
                }
                return new JSONMessage(true, $settingsForm->fetch($request));
        }
        return parent::manage($args, $request);
    }

    /**
     * @copydoc Plugin::getActions()
     */
    function getActions($request, $verb) {
        $router = $request->getRouter();
        import('lib.pkp.classes.linkAction.request.AjaxModal');
        return array_merge(
            $this->getEnabled()
                ? array(
                    new LinkAction(
                        'settings',
                        new AjaxModal(
                            $router->url(
                                $request,
                                null,
                                null,
                                'manage',
                                null,
                                array(
                                    'verb' => 'settings',
                                    'plugin' => $this->getName(),
                                    'category' => 'generic'
                                )
                            ),
                            $this->getDisplayName()
                        ),
                        __('manager.plugins.settings'),
                        null
                    ),
                )
                : array(),
            parent::getActions($request, $verb)
        );
    }

    /**
     * Insert VG Wort field vgWortCardNo into the user and the author form template
     */
    function metadataFieldEdit($hookName, $params) {
        $smarty =& $params[1];
        $output =& $params[2];
        if ($hookName == 'Common::UserDetails::AdditionalItems') {
            $smarty->assign('vgWortFieldTitle', 'plugins.generic.vgWort.cardNo');
        }
        $templateFile = method_exists($this, 'getTemplateResource')
            ? $this->getTemplateResource('vgWortCardNo.tpl')
            : $this->getTemplatePath() . 'vgWortCardNo.tpl';
        $output .= $smarty->fetch($templateFile);
        return false;
    }

    /**
     * Init VG Wort field vgWortCardNo in the user and the author form
     */
    function metadataInitData($hookName, $params) {
        $form =& $params[0];
        $user = null;
        if ($hookName == 'userdetailsform::initdata') {
            if (isset($form->userId)) {
                $userDao = DAORegistry::getDAO('UserDAO');
                $user = $userDao->getById($form->userId);
            }
        } elseif ($hookName == 'authorform::initdata') {
            $user = $form->getAuthor();
        } elseif ($hookName == 'publicprofileform::initdata') {
            $user = $form->getUser();
        }
        if ($user) {
            $form->setData('vgWortCardNo', $user->getData('vgWortCardNo'));
        }
        return false;
    }

    /**
     * Read the value of the VG Wort field vgWortCardNo in the user and the author form
     */
    function metadataReadUserVars($hookName, $params) {
        $form =& $params[0];
        $vars =& $params[1];
        $vars[] = 'vgWortCardNo';
        return false;
    }

    /**
     * Set the user or the author VG Wort card number
     */
    function metadataExecute($hookName, $params) {
        $form =& $params[0];
        $user = null;
        if ($hookName == 'userdetailsform::execute') {
            $user = $form->user;
        } elseif ($hookName == 'authorform::execute') {
            $user = $form->getAuthor();
        } elseif ($hookName == 'publicprofileform::execute') {
            $user =& $params[2];
        }
        if ($user) {
            $user->setData('vgWortCardNo', $form->getData('vgWortCardNo'));
        }
        return false;
    }

    /**
     * Add the validation check for the vgWortCardNo field (2-7 numbers)
     */
    function addCheck($hookName, $params) {
        $form =& $params[0];
        $form->addCheck(new FormValidatorRegExp(
            $form,
            'vgWortCardNo',
            'optional',
            'plugins.generic.vgWort.cardNoValid',
            '/^\d{2,7}$/'
        ));
        return false;
    }

    /**
     * Consider vgWortCardNo filed in the user and the author DAO
     */
    function addFieldName($hookName, $params) {
        $fields =& $params[1];
        $fields[] = 'vgWortCardNo';
        return false;
    }

    /**
     * Insert pixel tag assignemnt field into the PublicIdentifiersForm
     */
    function pixelEdit($hookName, $params) {
        $smarty =& $params[1];
        $output =& $params[2];

        $context = $smarty->get_template_vars('currentContext');
        $pubObject = $smarty->get_template_vars('pubObject');

        $pixelTag = $this->getPixelTagByPubObject($pubObject, $context->getId());
        $vgWortTextType = !isset($pixelTag) ? TYPE_TEXT : $pixelTag->getTextType();

        $excludeVGWortAssignPixel = $galleyNotSupported = null;
        if (is_a($pubObject, 'Representation')) {
            if (isset($pixelTag)) {
                if (!$pixelTag->getDateRemoved()) {
                    $excludeVGWortAssignPixel = $pubObject->getData('excludeVGWortAssignPixel');
                }
            } else {
                if (!$this->galleySupported($pubObject)) {
                    $galleyNotSupported = true;
                }
            }
        }
        $smarty->assign('pixelTag', $pixelTag);
        if ($excludeVGWortAssignPixel) {
            $smarty->assign('excludeVGWortAssignPixel', $excludeVGWortAssignPixel);
        }
        if ($galleyNotSupported) {
            $smarty->assign('galleyNotSupported', $galleyNotSupported);
        }
        $smarty->assign('vgWortTextType', $vgWortTextType);
        $smarty->assign('typeOptions', PixelTag::getTextTypeOptions());
        $templateFile = method_exists($this, 'getTemplateResource')
            ? $this->getTemplateResource('assignPixelTag.tpl')
            : $this->getTemplatePath() . 'assignPixelTag.tpl';
        $output .= $smarty->fetch($templateFile);
        return false;
    }

    /**
     * Read the value of the pixel tag assignemnt field in the PublicIdentifiersForm
     */
    function pixelReadUserVars($hookName, $params) {
        $form =& $params[1];
        $vars =& $params[2];
        $vars[] = 'vgWortTextType';
        $vars[] = 'vgWortAssignPixel';
        $vars[] = 'removeVGWortPixel';
        $vars[] = 'excludeVGWortAssignPixel';
        return false;
    }

    /**
     * Assign pixel tag to the galley object
     */
    function pixelExecuteRepresentation($hookName, $params) {
        $form =& $params[0];
        $pubObject = $form->getPubObject();
        $contextId = $form->getContextId();
        $pixelTag = $this->getPixelTagByPubObject($pubObject, $contextId);

        // Save the setting for a supported galley only if it is excluded from assignment
        if (is_a($pubObject, 'Representation') && $pixelTag && !$pixelTag->getDateRemoved()) {
            if ($this->galleySupported($pubObject)) {
                $galleyExcluded = $pubObject->getData('excludeVGWortAssignPixel');
                $excludeVGWortAssignPixel = $form->getData('excludeVGWortAssignPixel') ? 1 : 0;
                if (!$galleyExcluded && $excludeVGWortAssignPixel) {
                    $pubObject->setData('excludeVGWortAssignPixel', $excludeVGWortAssignPixel);
                } elseif ($galleyExcluded && !$excludeVGWortAssignPixel) {
                    $pubObject->setData('excludeVGWortAssignPixel', null);
                }
            }
        }
        return false;
    }

    /**
     * Assign pixel tag to the submission object
     */
    function pixelExecuteSubmission($hookName, $params) {
        $publication =& $params[0];
        $submissionId = $publication->getData('submissionId');
        $submissionFileDao =& DAORegistry::getDAO('SubmissionFileDAO');
        $submissionDao = DAORegistry::getDAO('SubmissionDAO');
        $submission = $submissionDao->getById($submissionId);
        $contextId = $submission->getData('contextId');
        $pixelTagDao = DAORegistry::getDAO('PixelTagDAO');
        $pixelTag = $pixelTagDao->getPixelTagBySubmissionId($submissionId, $contextId);

        if (is_a($submission, 'Submission')) {
            $vgWortTextType = $publication->getData('vgWort::texttype');
            if (isset($pixelTag)) {
                $updatePixelTag = false;
                // pixel tag has been removed, see if it should be assigned again
                if ($pixelTag->getDateRemoved()) {
                    $vgWortAssignPixel = $publication->getData('vgWort::pixeltag::assign') ? 1 : 0;
                    if ($vgWortAssignPixel) {
                        $pixelTag->setDateRemoved(null);
                        if ($pixelTag->getStatus() == PT_STATUS_UNREGISTERED_REMOVED) {
                            $pixelTag->setStatus(PT_STATUS_UNREGISTERED_ACTIVE);
                        } elseif ($pixelTag->getStatus() == PT_STATUS_REGISTERED_REMOVED) {
                            $pixelTag->setStatus(PT_STATUS_REGISTERED_ACTIVE);
                        }
                        $updatePixelTag = true;
                    }
                } else {
                    $removeVGWortPixel = $publication->getData('vgWort::pixeltag::assign') ? 0 : 1;
                    if ($removeVGWortPixel) {
                        $pixelTag->setDateRemoved(Core::getCurrentDate());
                        if ($pixelTag->getStatus() == PT_STATUS_UNREGISTERED_ACTIVE) {
                            $pixelTag->setStatus(PT_STATUS_UNREGISTERED_REMOVED);
                        } elseif ($pixelTag->getStatus() == PT_STATUS_REGISTERED_ACTIVE) {
                            $pixelTag->setStatus(PT_STATUS_REGISTERED_REMOVED);
                        }
                        $updatePixelTag = true;
                    }
                }
                // if text type changed, update
                // TODO: what if the pixel tag is registered
                if ($vgWortTextType != $pixelTag->getTextType()) {
                    $pixelTag->setTextType($vgWortTextType);
                    $updatePixelTag = true;
                }
                if ($updatePixelTag) {
                    $pixelTagDao = DAORegistry::getDAO('PixelTagDAO');
                    $pixelTagDao->updateObject($pixelTag);
                }
                $publication->setData('vgWort::pixeltag::status', $pixelTag->getStatus());
            } else {
                $vgWortAssignPixel = $publication->getData('vgWort::pixeltag::assign') ? 1 : 0;
                if ($vgWortAssignPixel) {
                    // assign pixel tag
                    if ($this->assignPixelTag($submission, $vgWortTextType)) {
                        $pixelTagStatus = 2; // unregistered, active
                        $publication->setData('vgWort::pixeltag::status', $pixelTagStatus);
                    }
                }
            }
        }
        return false;
    }

    /**
     * Consider pixel tag assignment filed in the article and the galley DAO
     */
    function addPixelFieldName($hookName, $params) {
        $fields =& $params[1];
        $fields[] = 'excludeVGWortAssignPixel';
        return false;
    }

    /**
     * Add several Pixel Tag properties
     */
    public function addToPublicationSchema($hookName, $args) {
        $schema = $args[0];
        // Add property for the text type
        $schema->properties->{"vgWort::texttype"} = (object) [
            'type' => 'string',
        ];
        // Add property for assigning the pixel tag
        $schema->properties->{"vgWort::pixeltag::assign"} = (object) [
            'type' => 'boolean',
            'validation' => ['nullable']
        ];
        // Add property for removing the pixel tag
        $schema->properties->{"vgWort::pixeltag::remove"} = (object) [
            'type' => 'boolean',
            'validation' => ['nullable']
        ];
        // Add property for pixel tag's status
        $schema->properties->{"vgWort::pixeltag::status"} = (object) [
            'type' => 'string',
        ];
        return false;
    }

    /**
     * Add vgWorgCardNo property to author schema
     */
    public function addToAuthorSchema($hookName, $args) {
        $schema = $args[0];
        // Add property for the vgWortCardNo
        $schema->properties->{"vgWortCardNo"} = (object) [
            'type' => 'integer'
        ];
        return false;
    }

    /**
	 * Add a new form tab for the VG Wort.
	 */
	function addVGWortFormTab($hookName, $args) {
		$html =& $args[2];
		$html = '<tab id="vgwortformtab" label="VG Wort">
			<pkp-form v-bind="components.'. FORM_VGWORT . '" @set="set" />
		</tab>';
		return false;
	}

    /**
     * Get the pixel tag
     * @param $pubObject Article|ArticleGalley
     * @param $contextId int
     * @return PixelTag|null
     */
    function getPixelTagByPubObject($pubObject, $contextId) {
        if (is_a($pubObject, 'Submission')) {
            $submissionId = $pubObject->getData('id');//getId();
        } elseif (is_a($pubObject, 'Representation')) {
            $publicationId = $pubObject->getData('publicationId');
            $publicationDao = DAORegistry::getDAO('PublicationDAO');
            $publication = $publicationDao->getById($publicationId);
            $submissionId = $publication->getData('submissionId');
        } else {
            return null;
        }
        $pixelTagDao = DAORegistry::getDAO('PixelTagDAO');
        return $pixelTagDao->getPixelTagBySubmissionId($submissionId, $contextId);
    }

    /**
     * Check if the galley is supported and thus
     * if the pixel tag can be assigned to this galley.
     * Only full text files in format s PDF, HTML or EPUB are supported,
     * that are not bigger then 15MB.
     * @param $galley ArticleGalley
     * @return boolean
     */
    function galleySupported($galley) {
        // check if the galley is a full text
        $galleyFile = $galley->getFile();
        if (!$galleyFile) {
            return false;
        }
        $genreDao = DAORegistry::getDAO('GenreDAO');
        $genre = $genreDao->getById($galleyFile->getGenreId());
        if ($genre->getCategory() != GENRE_CATEGORY_DOCUMENT || $genre->getSupplementary() || $genre->getDependent()) {
            return false;
        }
        // check the file size (<=15MB)
        $megaByte = 1024*1024;
        if (round((int) $galleyFile->getFileSize() / $megaByte) > 15) {
            return false;
        }
        // check the galley file format
        return $this->fileTypeSupported($galleyFile->getFileType());
    }

    /**
     * Is the file type supported i.e. is it either PDF, HTML or EPUB
     * @param $galleyFileType string
     * @return boolean
     */
    function fileTypeSupported($galleyFileType) {
        return ($galleyFileType == 'application/pdf' ||
        $galleyFileType == 'application/epub+zip' ||
        // Added XML support. Check with vgWort if allowed
        $galleyFileType == 'text/xml' ||
        $galleyFileType == 'text/html');
    }

    /**
     * Check if the galley is only for download i.e. check:
     * if it is a full text, max. 15MB, and a PDF, an HTML or an EPUB file,
     * if the PDF and HTML view plugins are enabled, and if the galley was excluded from the VG Wort pixel assignment.
     * @param $galley ArticleGalley
     * @return boolean
     */
    function filterDownloadGalleys($galley) {
        // check if it is a full text
        $galleyFile = $galley->getFile();
        if (!$galleyFile) {
            return false;
        }
        $genreDao = DAORegistry::getDAO('GenreDAO');
        $genre = $genreDao->getById($galleyFile->getGenreId());
        if ($genre->getCategory() != GENRE_CATEGORY_DOCUMENT ||
        $genre->getSupplementary() ||
        $genre->getDependent()) {
            return false;
        }
        // check if the file size is bigger than 15MB
        $megaByte = 1024*1024;
        if (round((int) $galleyFile->getFileSize() / $megaByte) > 15) {
            return false;
        }
        // check if the galley is for download
        $pdfJsViewerPlugin = PluginRegistry::getPlugin('generic', 'pdfjsviewerplugin');
        $htmlArticleGalley = PluginRegistry::getPlugin('generic', 'htmlarticlegalleyplugin');
        return (($galleyFile->getFileType() == 'text/html' && !$htmlArticleGalley) ||
            ($galley->isPdfGalley() && !$pdfJsViewerPlugin) ||
            $galley->getFileType() == 'application/epub+zip') &&
            !$galley->getData('excludeVGWortAssignPixel');
    }

    /**
     * Assign a pixel tag to an article.
     * @param $article Article
     * @param $vgWortTextType int
     * @return boolean
     */
    function assignPixelTag($article, $vgWortTextType) {
        $pixelTagDao = DAORegistry::getDAO('PixelTagDAO');
        $contextId = $article->getContextId();

        // TODO: it seems possible to order just 1 pixel tag
        $availablePixelTag = $pixelTagDao->getAvailablePixelTag($contextId);

        if(!$availablePixelTag) {
            // order pixel tags
            $this->import('classes.VGWortEditorAction');
            $vgWortEditorAction = new VGWortEditorAction($this);
            $orderResult = $vgWortEditorAction->orderPixel($contextId);
            if (!$orderResult[0]) {
                $application = PKPApplication::getApplication();
                $request = Application::get()->getRequest();
                $user = $request->getUser();
                // Create a form error notification.
                import('classes.notification.NotificationManager');
                $notificationManager = new NotificationManager();
                $notificationManager->createTrivialNotification(
                    $user->getId(), NOTIFICATION_TYPE_FORM_ERROR, array('contents' => $orderResult[1])
                );
                return false;
            } else {
                // insert ordered pixel tags in the db
                $vgWortEditorAction->insertOrderedPixel($contextId, $orderResult[1]);
            }
            $availablePixelTag = $pixelTagDao->getAvailablePixelTag($contextId);
        }
        assert($availablePixelTag);
        // there is an available pixel tag --> assign
        $availablePixelTag->setSubmissionId($article->getId());
        $availablePixelTag->setDateAssigned(Core::getCurrentDate());
        $availablePixelTag->setStatus(PT_STATUS_UNREGISTERED_ACTIVE);
        $availablePixelTag->setTextType($vgWortTextType);
        $pixelTagDao->updateObject($availablePixelTag);
        return true;
    }

    /**
     * Add/display the Pixel Tags tab.
     */
    function pixelTagTab($hookName, $params) {
        $smarty =& $params[1];
        $output =& $params[2];
        $templateFile = method_exists($this, 'getTemplateResource')
            ? $this->getTemplateResource('distributionNavLink.tpl')
            : $this->getTemplatePath() . 'distributionNavLink.tpl';
        $output .= $smarty->fetch($templateFile);
        return false;
    }

    /**
     * Handle the article view and the issue view template display.
     */
    function handleTemplateDisplay($hookName, $params) {
        $smarty =& $params[0];
        $template =& $params[1];
        $ojsVersion = Application::getApplication()->getCurrentVersion()->getVersionString();
        // the template for the pdf viewer looks like this:
        // plugins-13-plugins-generic-pdfJsViewer-generic-pdfJsViewer:submissionGalley.tpl
        // number (contextId?) depends on journal, thus string is not static
        if (strstr($template,"submissionGalley.tpl")) {
            $smarty->registerFilter('output',array($this, 'insertPixelTagArticlePage'));
            return false;
        }
        switch ($template) {
            case 'frontend/pages/article.tpl':
                $smarty->registerFilter('output',array($this, 'insertPixelTagArticlePage'));
                break;
            case 'frontend/pages/indexJournal.tpl':
            case 'frontend/pages/issue.tpl':
                $smarty->registerFilter('output',array($this, 'insertPixelTagIssueTOC'));
                break;
            case 'workflow/workflow.tpl':
				$this->import('classes.form.VGWortForm');
				$context = $smarty->getTemplateVars('currentJournal');
				$submission = $smarty->getTemplateVars('submission');
				$request = Application::get()->getRequest();
				$latestPublicationApiUrl = $request->getDispatcher()->url(
                    $request,
                    ROUTE_API,
                    $context->getPath(),
                    'submissions/' . $submission->getId() . '/publications/' . $submission->getLatestPublication()->getId()
                );
				$form = new VGWortForm($latestPublicationApiUrl, [], $context, $submission);
				$workflowData = $smarty->getTemplateVars('workflowData');
				$workflowData['components'][FORM_VGWORT] = $form->getConfig();
				$smarty->assign('workflowData', $workflowData);
				$smarty->addJavaScript('vgWort-labels',
					'window.vgWortPixeltagStatusLabels = ' . json_encode($this->pixelTagStatusLabels) . ';',
					[
						'inline' => true,
						'contexts' => 'backend',
						'priority' => STYLE_SEQUENCE_CORE
					]
				);
            $smarty->addJavaScript(
                'vgwort',
                Application::get()->getRequest()->getBaseUrl()
                    . DIRECTORY_SEPARATOR
                    . $this->getPluginPath()
                    . DIRECTORY_SEPARATOR . 'js'
                    . DIRECTORY_SEPARATOR . 'vgwort.js',
                [
                    'contexts' => 'backend',
                    'priority' => STYLE_SEQUENCE_LAST
                ]);
            break;
        }
        return false;
    }

    /**
     * Handle the submission production view.
     */
    function handleTemplateFetch($hookName, $params) {
        $smarty =& $params[0];
        $template =& $params[1];
        switch ($template) {
            case 'controllers/tab/workflow/production.tpl':
                $submission = $smarty->get_template_vars('submission');
                $notificatinOptions =& $smarty->get_template_vars('productionNotificationRequestOptions');
                $notificatinOptions[NOTIFICATION_LEVEL_NORMAL][NOTIFICATION_TYPE_VGWORT_ERROR] = array(
                    ASSOC_TYPE_SUBMISSION,
                    $submission->getId()
                );
            break;
        }
        return false;
    }

    /**
     * Build the <img> Tag for a given pixel tag object
     */
    function buildPixelTagHTML($pixelTag, $https = false) {
        $httpProtocol = $https ? 'https://' : 'http://';
        // construct pixel URL and image that will be inserted
        $pixelTagSrc =  $httpProtocol . $pixelTag->getDomain() . '/na/' . $pixelTag->getPublicCode();
        return '<img src=\'' . $pixelTagSrc . '\' width=\'1\' height=\'1\' alt=\'\' />';
    }

    /**
     * Add VG Wort Pixel to heiViewer Article Galley page
     */
    function insertPixelTagGalleyPageHook($hookName, $params) {
        $smarty =& $params[1];
        $output =& $params[2];

        $journal = $smarty->get_template_vars('currentJournal');
        $article = $smarty->get_template_vars('article');
        $pixelTagDao = DAORegistry::getDAO('PixelTagDAO');
        $pixelTag = $pixelTagDao->getPixelTagBySubmissionId($article->getId(), $journal->getId());
        if (isset($pixelTag) && !$pixelTag->getDateRemoved()) {
            $application = PKPApplication::getApplication();
            $request = $application->getRequest();
            $httpsProtocol = $request->getProtocol() == 'https';
            $output = $this->buildPixelTagHTML($pixelTag, $httpsProtocol);
        }
        return false;
    }

    /**
     * Insert the VG Wort pixel tag for galleys on the article view page.
     */
    function insertPixelTagArticlePage($output, $smarty) {
        $journal = $smarty->get_template_vars('currentJournal');
        $article = $smarty->get_template_vars('article');

        // get the galley if it exists and check if it is supported by VG Wort
        // currently the variable is provided only by the PdfJSViewerPlugin and the HTMLArticleGalleyPlugin
        $galley = $smarty->get_template_vars('galley');
        if (!$galley || !$this->galleySupported($galley)) {
            $galley = null;
        }

        // if it is the article view page, get the primary galleys
        $galleys = $smarty->get_template_vars('primaryGalleys');
        // get only download galleys, that should get the VG Wort pixel tag
        $downloadGalleys = array_filter($galleys, array($this, 'filterDownloadGalleys'));
        $submissionDao = Application::getSubmissionDAO(); // Application allows usage for both OJS and OMP
        $submission = $submissionDao->getById($article->getId());
        if (isset($submission)) {
            $issueDao = DAORegistry::getDAO('IssueDAO');
            $issue = $issueDao->getById($submission->getCurrentPublication()->getData('issueId'), $journal->getId());
            if ($issue->getPublished()) {
                // get the assigned pixel tag
                $pixelTagDao = DAORegistry::getDAO('PixelTagDAO');
                $pixelTag = $pixelTagDao->getPixelTagBySubmissionId($submission->getId(), $journal->getId());
                if (isset($pixelTag) && !$pixelTag->getDateRemoved()) {
                    $application = PKPApplication::getApplication();
                    $request = $application->getRequest();
                    $httpProtocol = $request->getProtocol() == 'https' ? 'https://' : 'http://';
                    // construct pixel URL and image that will be inserted
                    $pixelTagSrc =  $httpProtocol . $pixelTag->getDomain() . '/na/' . $pixelTag->getPublicCode();
                    $pixelTagImg = '<img src=\'' . $pixelTagSrc . '\' width=\'1\' height=\'1\' alt=\'\' />';

                    // if galley exists it is the galley view page
                    // currently provided only by PdfJSViewerPlugin and HTMLArticleGalleyPlugin
                    // check if the galley is excluded from VG Wort pixel tag assignment
                    if ($galley && !$galley->getData('excludeVGWortAssignPixel')) {
                        // isert the pixel tag image into the HTML code
                        $search = '</header>';
                        $replace = $search . $pixelTagImg;
                        $output = str_replace($search, $replace, $output);

                        // if it is a PDF galley there will be the download link
                        if ($galley->isPdfGalley()) {
                            // insert the JS function, that is used when the download link is clicked on
                            $search = '<header class="header_view">';
                            $replace = $search . '<script>function vgwPixelCall() { document.getElementById("div_vgwpixel").innerHTML="<img src=\'' . $pixelTagSrc . '\' width=\'1\' height=\'1\' alt=\'\' />"; }</script>';
                            $output = str_replace($search, $replace, $output);

                            // change the galley download link
                            // insert pixel tag for galleys download links using JS
                            // when download link is clicked on, the pixel tag image should be inserted after the header in order not to distroy the layout
                            $search = '</header>';
                            $replace = $search . '<div id="div_vgwpixel"></div>';
                            $output = str_replace($search, $replace, $output);
                            // Note: the galley download URL is without the file ID but with the ending "/"
                            $galleyUrl = $request->url(null, 'article', 'download', array($submission->getBestArticleId(), $galley->getBestGalleyId())) . '/';
                            $search = '<a href="' . $galleyUrl . '" class="download" download>';
                            $replace = '<a href="' . $galleyUrl . '" onclick="vgwPixelCall();" class="download" download>';
                            // insert pixel tag for galleys download links using VG Wort redirect
                            $output = str_replace($search, $replace, $output);
                        }
                    } elseif (!$galley && !empty($downloadGalleys)) { // it is the article view page and there are download galley links there
                        // insert the JS function, used when galley links are clicked on
                        $search = '<article class="obj_article_details">';
                        $replace = $search . '<script>function vgwPixelCall(galleyId) { document.getElementById("div_vgwpixel_"+galleyId).innerHTML="<img src=\'' . $pixelTagSrc . '\' width=\'1\' height=\'1\' alt=\'\' />"; }</script>';
                        $output = str_replace($search, $replace, $output);
                        foreach ($downloadGalleys as $galley) {
                            // change galley download links
                            $galleyUrl = $request->url(null, 'article', 'view', array($submission->getBestArticleId(), $galley->getBestGalleyId()));
                            $search = '#<a class="(.+)" href="' . $galleyUrl . '">#';
                            // insert pixel tag for galleys download links using JS
                            $replace = '<div style="font-size:0;line-height:0;width:0;" id="div_vgwpixel_' . $galley->getId() . '"></div><a class="$1" href="' . $galleyUrl . '" onclick="vgwPixelCall(' . $galley->getId() . ');">';
                            // insert pixel tag for galleys download links using VG Wort redirect
                            $output = preg_replace($search, $replace, $output);
                        }
                    }
                }
            }
        }
        $ojsVersion = Application::getApplication()->getCurrentVersion()->getVersionString();
        //unregister filter if we reached the page body end tag
        if (preg_match('#</body>#', $output)) {
            $smarty->unregisterFilter('output', array($this, 'insertPixelTagArticlePage'));
        }
        return $output;
    }

    /**
     * Insert the VG Wort pixel tag for galleys on the issue TOC page.
     */
    function insertPixelTagIssueTOC($output, $smarty) {
        $journal = $smarty->get_template_vars('currentJournal');
        $issue = $smarty->get_template_vars('issue');

        $publishedSubmissions = $smarty->get_template_vars('publishedSubmissions');
        if (isset($issue) && !empty($issue)) {
            if ($issue->getPublished()) {
                $scriptInserted = false;
                foreach ($publishedSubmissions as $sectionId) {
                    foreach ($sectionId['articles'] as $submission) {
                        // get the assigned pixel tag
                        $pixelTagDao = DAORegistry::getDAO('PixelTagDAO');
                        $pixelTag = $pixelTagDao->getPixelTagBySubmissionId($submission->getId(), $journal->getId());
                        if (isset($pixelTag) && !$pixelTag->getDateRemoved()) {
                            $application = PKPApplication::getApplication();
                            $request = $application->getRequest();
                            $httpProtocol = $request->getProtocol() == 'https' ? 'https://' : 'http://';
                            // construct pixel URL and image that will be inserted
                            $pixelTagSrc = $httpProtocol . $pixelTag->getDomain() . '/na/' . $pixelTag->getPublicCode();
                            $pixelTagImg = '<img src=\'' . $pixelTagSrc . '\' width=\'1\' height=\'1\' alt=\'\' />';

                            $articleGalleys = $submission->getGalleys();
                            // get only download galleys, that should get the VG Wort pixel tag
                            $downloadGalleys = array_filter($articleGalleys, array($this, 'filterDownloadGalleys'));

                            if (!empty($downloadGalleys) && !$scriptInserted) {
                                // insert the JS function, used when galley links are clicked on
                                $search = '<div class="obj_issue_toc">';
                                $replace = $search . '<script>function vgwPixelCall(galleyId) { document.getElementById("div_vgwpixel_"+galleyId).innerHTML="<img src=\'' . $pixelTagSrc . '\' width=\'1\' height=\'1\' alt=\'\' />"; }</script>';
                                $output = str_replace($search, $replace, $output);
                                $scriptInserted = true;
                            }
                            foreach ($downloadGalleys as $galley) {
                                // change galley download links
                                $galleyUrl = $request->url(null, 'article', 'view', array($submission->getBestArticleId(), $galley->getBestGalleyId()));
                                $search = '#<a class="(.+)" href="' . $galleyUrl . '"(.*)>#';
                                // insert pixel tag for galleys download links using JS
                                $replace = '<div style="font-size:0;line-height:0; width:0;" id="div_vgwpixel_' . $galley->getId() . '"></div><a class="$1" href="' . $galleyUrl . '" onclick="vgwPixelCall(' . $galley->getId() . ');" target="_target" $2>';
                                // insert pixel tag for galleys download links using VG Wort redirect
                                $output = preg_replace($search, $replace, $output);
                            }
                        }
                    }
                }
            }
        }
        $ojsVersion = Application::getApplication()->getCurrentVersion()->getVersionString();
        // Unregister filter if we reached the page body end tag
        if (preg_match('#</body>#', $output)) {
            $smarty->unregisterFilter('output', array($this, 'insertPixelTagIssueTOC'));
        }
        return $output;
    }

    /**
     * Get the VG Wort error notification message.
     */
    function getNotificationMessage($hookName, $params) {
        $notification =& $params[0];
        $message =& $params[1];
        switch ($notification->getType()) {
            case NOTIFICATION_TYPE_VGWORT_ERROR:
            $message = __('plugins.generic.vgWort.notification.vgWortError');
            break;
        }
        return false;
    }

    /**
     * Set up the pixel tags grid handler.
     */
    function setupGridHandler($hookName, $params) {
        $component =& $params[0];
        if ($component == 'plugins.generic.vgWort.controllers.grid.PixelTagGridHandler') {
            define('VGWORT_PLUGIN_NAME', $this->getName());
            return true;
        }
        return false;
    }

}

?>
