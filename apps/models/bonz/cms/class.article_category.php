<?php
namespace Bonz\CMS;
use \Bonz\Db\Table;

class Article_Category extends Table{
	protected $_tablename = 'article_category';
	/**
	 * Primary key
	 * @var int
	 */
	var $catid;
	/**
	 * Reference to Article
	 * @var int
	 */
	var $aid;
	/**
	 * Category name
	 * @var string
	 */
	var $name;
	/**
	 * Litle description or title
	 * @var string
	 */
	var $label;
	/**
	 * Reference to Files -> the category picture
	 * @var int
	 */
	var $fid;
	/**
	 * Reference to a parent Article_Category
	 * @var int
	 */
	var $parent_catid;
	/**
	 * Reference to User that has created the category
	 * @var int
	 */
	var $cre_uid;
	/**
	 * Reference to User that has latestly modified the category
	 * @var int
	 */
	var $upd_uid;
	/**
	 * Creation date
	 * @var Date
	 */
	var $cre_date;
	/**
	 * Last update date
	 * @var Date
	 */
	var $upd_date;
	/**
	 * Fetch the parent category object
	 * @return Article_Category
	 */
	function getParent(){
		if (!$this->parent_catid)
			return false;
		static $Parent;
		if (is_a($Parent, __CLASS__))
			return $Parent;
		$Parent = new Article_Category();
		$Parent->load("catid=$this->parent_catid");
		return $Parent;
	}
	
	/**
	 * Fetch all Articles from this category
	 * @return array of Article objects
	 */
	function getArticles(){
		static $articles;
		if (!empty($articles))
			return $articles;
		$Article = new Article();
		$articles = $Article->select('catid='.$this->catid, null, 'aid ASC', true);
		return $articles;
	}
	
	/**
	 * Fetch a hierarchical tree of categories
	 * @param int	$catid
	 * @param int	$depth
	 * @param bool	$restart	started depth count
	 * @return array
	 */
	function getTree($catid=null, $depth=null, &$inDepth=-1){
		if ($depth){
			$inDepth++;
			## Stop process when depth defined is reached
			if ($depth && $depth<=$inDepth) return;
		}
		$categories = array();
		$article = new Article();
		$image = new Files();
		$condition = empty($catid) ? "C.parent_catid IS NULL OR C.parent_catid = '' OR C.parent_catid = 0" : "C.parent_catid  = '$catid'";
		$query = 'SELECT C.*,I.path AS image_filepath, A.title AS article_title, A.request_uri, A.uid, A.publish, A.edit_date FROM '.
			$this->getFullTableName().' AS C LEFT JOIN '.
			$article->getFullTableName().' AS A ON A.aid = C.aid LEFT JOIN '.
			$image->getFullTableName().' AS I ON I.fid = C.fid WHERE '.$condition;
		if ($parents = $this->_db->getTable($query)){
			foreach ($parents as $category){
				$depthRelay = $inDepth;
				$categories[$category['catid']] = array('data'=>$category, 'children'=>$this->getTree($category['catid'], $depth, $depthRelay));
			}
		}
		return $categories;
	}
	
	/**
	 * @desc In conditions, you can use "where", "order", "limit", "group" etc.
	 * You have to use table aliases: "C" for base "article_category" table, "P" for "parent article_category", "A" for "Article" and "I" for "Files" table (as image).
	 * @param string $strAllConds	Intending to customize this query, these conditions are extended
	 * @return array
	 */
	function getGrid($strAllConds=''){
		$query = "SELECT C.catid, CONCAT(P.name, ' (CATID ', P.catid, ')') AS parent_category, C.name, C.label, ".
				"I.filename AS image_filename, I.path AS image_filepath, I.mime_type AS image_mimetype, ".
				"A.title, A.request_uri, A.publish, A.edit_date ".
			"FROM article_category AS C LEFT JOIN article_category AS P ON C.parent_catid = P.catid ".
			"LEFT JOIN files AS I ON C.fid = I.fid ".
			"LEFT JOIN article AS A ON A.aid = C.aid ".
			$strAllConds;
		return $this->_db->getTable($query);
	}
}