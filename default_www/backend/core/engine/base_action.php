<?php

/**
 * BackendBaseAction
 *
 * This class implements a lot of functionality that can be extended by a specific action
 *
 * @package		backend
 * @subpackage	core
 *
 * @author 		Tijs Verkoyen <tijs@netlash.com>
 * @since		2.0
 */
class BackendBaseAction
{
	/**
	 * The current action
	 *
	 * @var	string
	 */
	protected $action;


	/**
	 * The parameters (urldecoded)
	 *
	 * @var	array
	 */
	protected $parameters = array();


	/**
	 * The header object
	 *
	 * @var	BackendHeader
	 */
	protected $header;


	/**
	 * The current module
	 *
	 * @var	string
	 */
	protected $module;


	/**
	 * A reference to the current template
	 *
	 * @var	BackendTemplate
	 */
	public $tpl;


	/**
	 * A reference to the URL-instance
	 *
	 * @var	BackendURL
	 */
	public $URL;


	/**
	 * Default constructor
	 * The constructor will set some properties. It populates the parameter array with urldecoded values for easy-use.
	 *
	 * @return	void
	 */
	public function __construct()
	{
		// get objects from the reference so they are accessable from the action-object
		$this->tpl = Spoon::getObjectReference('template');
		$this->URL = Spoon::getObjectReference('url');
		$this->header = Spoon::getObjectReference('header');

		// store the current module and action (we grab them from the URL)
		$this->setModule($this->URL->getModule());
		$this->setAction($this->URL->getAction());

		// populate the parameter array, we loop GET and urldecode the values for usage later on
		foreach((array) $_GET as $key => $value)
		{
			// is the value an array?
			if(is_array($value))
			{
				// urldecode each element in the array (REMARK: we don't support multidim arrays)
				// arrays in GET are ugly and stupid
				$this->parameters[$key] = (array) array_map('urldecode', $value);
			}

			// it's just a string
			else $this->parameters[$key] = urldecode($value);
		}
	}


	/**
	 * Display, this wil output the template to the browser
	 * If no template is specified we build the path form the current module and action
	 *
	 * @return	void
	 * @param	string[optional] $template
	 */
	public function display($template = null)
	{
		// parse header
		$this->header->parse();

		// if no template is specified, we have to build the path ourself
		// the default template is based on the name of the current action
		if($template === null) $template = BACKEND_MODULE_PATH .'/layout/templates/'. $this->URL->getAction() .'.tpl';

		// display
		$this->tpl->display($template);
	}


	/**
	 * Execute the action
	 *
	 * @return	void
	 */
	public function execute()
	{
		// add jquery, we will need this in every action, so add it globally
		$this->header->addJavascript('jquery/jquery.js', 'core');
		$this->header->addJavascript('jquery/jquery.ui.js', 'core');
		$this->header->addJavascript('jquery/jquery.autocomplete.js', 'core');
		$this->header->addJavascript('jquery/jquery.backend.js', 'core');

		// add items that always need to be loaded
		$this->header->addJavascript('backend.js', 'core', true);
		$this->header->addJavascript('utils.js', 'core', true);

		// add default js file (if the file exists)
		if(SpoonFile::exists(BACKEND_MODULE_PATH .'/js/'. $this->getModule() .'.js')) $this->header->addJavascript($this->getModule() .'.js', null, true);
		if(SpoonFile::exists(BACKEND_MODULE_PATH .'/js/'. $this->getAction() .'.js')) $this->header->addJavascript($this->getAction() .'.js', null, true);

		// add css
		$this->header->addCSS('screen.css', 'core');
		$this->header->addCSS('jquery_ui/fork/jquery_ui.css', 'core');

		// debug css
		if(SPOON_DEBUG) $this->header->addCSS('debug.css', 'core');
	}


	/**
	 * Get the action
	 *
	 * @return	string
	 */
	public function getAction()
	{
		return $this->action;
	}


	/**
	 * Get the module
	 *
	 * @return	string
	 */
	public function getModule()
	{
		return $this->module;
	}


	/**
	 * Get a parameter for a given key
	 * The function will return null if the key is not available
	 *
	 * By default we will cast the return value into a string, if you want something else specify it by passing the wanted type.
	 * Possible values are: bool, boolean, int, integer, float, double, string, array
	 *
	 * @return	mixed
	 * @param	string $key
	 * @param	string[optional] $type
	 */
	public function getParameter($key, $type = 'string')
	{
		// redefine key
		$key = (string) $key;

		// parameter exists
		if(isset($this->parameters[$key]) && $this->parameters[$key] != '') return SpoonFilter::getValue($this->parameters[$key], null, null, $type);

		// no such parameter
		return null;
	}


	/**
	 * Redirect to a given URL
	 *
	 * @return	void
	 * @param	string $URL
	 */
	public function redirect($URL)
	{
		SpoonHTTP::redirect((string) $URL);
	}


	/**
	 * Set the action, for later use
	 *
	 * @return	void
	 * @param	string $action
	 * @param	string $action
	 */
	private function setAction($action)
	{
		$this->action = (string) $action;
	}


	/**
	 * Set the module, for later use
	 *
	 * @return	void
	 * @param	string $module
	 */
	private function setModule($module)
	{
		$this->module = (string) $module;
	}
}


/**
 * BackendBaseActionIndex
 *
 * This class implements a lot of functionality that can be extended by the real action.
 * In this case this is the base class for the index action
 *
 * @package		Backend
 * @subpackage	core
 *
 * @author 		Tijs Verkoyen <tijs@netlash.com>
 * @since		2.0
 */
class BackendBaseActionIndex extends BackendBaseAction
{
	/**
	 * A datagrid instance
	 *
	 * @var	BackendDataGridDB
	 */
	protected $datagrid;


	/**
	 * Execute the current action
	 * This method will be overwriten in most of the actions, but still be called to add general stuff
	 *
	 * @return	void
	 */
	public function execute()
	{
		// call parent, will add general CSS and JS
		parent::execute();

		// is there a report to show?
		if($this->getParameter('report') !== null)
		{
			// show the report
			$this->tpl->assign('report', true);

			// camelcase the string
			$messageName = SpoonFilter::toCamelCase($this->getParameter('report'));

			// if we have data to use it will be passed as the var-parameter, if so assign it
			if($this->getParameter('var') !== null) $this->tpl->assign('reportMessage', sprintf(BackendLanguage::getMessage($messageName), $this->getParameter('var')));
			else $this->tpl->assign('reportMessage', $messageName);

			// hightlight an element with the given id if needed
			if($this->getParameter('highlight')) $this->tpl->assign('highlight', $this->getParameter('highlight'));
		}

		// is there an error to show?
		if($this->getParameter('error') !== null)
		{
			// show the error and the errormessage
			$this->tpl->assign('errorMessage', BackendLanguage::getError(SpoonFilter::toCamelCase($this->getParameter('error'), '-')));
		}
	}
}


/**
 * BackendBaseActionAdd
 *
 * This class implements a lot of functionality that can be extended by the real action.
 * In this case this is the base class for the add action
 *
 * @package		Backend
 * @subpackage	core
 *
 * @author 		Tijs Verkoyen <tijs@netlash.com>
 * @since		2.0
 */
class BackendBaseActionAdd extends BackendBaseAction
{
	/**
	 * The form instance
	 *
	 * @var	BackendForm
	 */
	protected $frm;


	/**
	 * The backends meta-object
	 *
	 * @var	BackendMeta
	 */
	protected $meta;


	/**
	 * Parse the form
	 *
	 * @return	void
	 */
	protected function parse()
	{
		// parse form
		$this->frm->parse($this->tpl);
	}
}


/**
 * BackendBaseActionEdit
 *
 * This class implements a lot of functionality that can be extended by the real action.
 * In this case this is the base class for the edit action
 *
 * @package		Backend
 * @subpackage	core
 *
 * @author 		Tijs Verkoyen <tijs@netlash.com>
 * @since		2.0
 */
class BackendBaseActionEdit extends BackendBaseAction
{
	/**
	 * Datagrid with the revisions
	 *
	 * @var	BackendDataGridDB
	 */
	protected $dgRevisions;


	/**
	 * The form instance
	 *
	 * @var	BackendForm
	 */
	protected $frm;


	/**
	 * The id of the item to edit
	 *
	 * @var	int
	 */
	protected $id;


	/**
	 * The backends meta-object
	 *
	 * @var	BackendMeta
	 */
	protected $meta;


	/**
	 * The data of the item to edit
	 *
	 * @var	array
	 */
	protected $record;


	/**
	 * Parse the form
	 *
	 * @return	void
	 */
	protected function parse()
	{
		$this->frm->parse($this->tpl);
	}
}


/**
 * BackendBaseActionDelete
 *
 * This class implements a lot of functionality that can be extended by the real action.
 * In this case this is the base class for the delete action
 *
 * @package		Backend
 * @subpackage	core
 *
 * @author 		Tijs Verkoyen <tijs@netlash.com>
 * @since		2.0
 */
class BackendBaseActionDelete extends BackendBaseAction
{
	/**
	 * The id of the item to edit
	 *
	 * @var	int
	 */
	protected $id;


	/**
	 * The data of the item to edite
	 *
	 * @var	array
	 */
	protected $record;


	/**
	 * Execute the current action
	 * This method will be overwriten in most of the actions, but still be called to add general stuff
	 *
	 * @return	void
	 */
	public function execute()
	{
	}
}

?>