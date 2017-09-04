<?php
class Mivec_Page_Pager extends Mivec_Page_Abstract
{
    private $_navigationItemCount = 10;                //导航栏显示导航总页数
    private $_pageSize = null;                        //每页项目数
    private $_align = "left";                        //导航栏显示位置
    private $_itemCount = null;                        //总项目数
    private $_pageCount = null;                        //总页数
    private $_currentPage = null;                    //当前页
    private $_front = null;                            //前端控制器
	
    private $_PageParaName = "page";                //页面参数名称

    private $_firstPageString = "|<";                //导航栏中第一页显示的字符
    private $_nextPageString = ">";                //导航栏中前一页显示的字符
    private $_previousPageString = "<";            //导航栏中后一页显示的字符
    private $_lastPageString = ">|";                //导航栏中最后一页显示的字符
    private $_splitString = " | ";					//页数字间的间隔符 /

    public function __construct($itemCount, $pageSize)
    {
        if(!is_numeric($itemCount) || (!is_numeric($pageSize)))
        throw new Exception("Pagination Error:not Number");
		
        $this->_itemCount = $itemCount;
        $this->_pageSize = $pageSize;
        //$this->_front = Zend_Controller_Front::getInstance();
		
        $this->_pageCount = ceil($itemCount / $pageSize);            //总页数
        //$page = $this->_front->getRequest()->getParam($this->_PageParaName);
		$page = $_GET[$this->_PageParaName];
		
        if(empty($page) || (!is_numeric($page)))    //为空或不是数字，设置当前页为1
        {
            $this->_currentPage = 1;
        }
        else
        {
            if($page < 1)
                $page = 1;
            if($page > $this->_pageCount)
                $page = $this->_pageCount;
            $this->_currentPage = $page;
        }
    }

    /**
     * 返回当前页
     * @param int 当前页
     */
    public function getCurrentPage()
    {
        return $this->_currentPage;
    }

    /**
     * 返回导航栏目
     * @return string 导航html                class="PageNavigation" 
     */
    public function getNavigation()
    {
        $navigation = '<div class="pager" style="text-align:'.$this->_align.'">';

        $pageCote = ceil($this->_currentPage / ($this->_navigationItemCount - 1)) - 1;    //当前页处于第几栏分页
        $pageCoteCount = ceil($this->_pageCount / ($this->_navigationItemCount - 1));    //总分页栏
        $pageStart = $pageCote * ($this->_navigationItemCount -1) + 1;                    //分页栏中起始页
        $pageEnd = $pageStart + $this->_navigationItemCount - 1;                        //分页栏中终止页
        if($this->_pageCount < $pageEnd)
        {
            $pageEnd = $this->_pageCount;
        }
                //$navigation .= "总共：{$this->_itemCount}条　{$this->_pageCount}页\n";
				$navigation .= "Total：{$this->_itemCount} &nbsp;&nbsp;&nbsp;&nbsp;\n";
        if($pageCote > 0)                                //首页导航
        {
            $navigation .= '<a href="'.$this->createHref(1)."\">$this->_firstPageString</a> ";
        }
        if($this->_currentPage != 1)                    //上一页导航
        {
            $navigation .= '<a href="'.$this->createHref($this->_currentPage-1);
            $navigation .= "\">$this->_previousPageString</a> ";
        }
        while ($pageStart <= $pageEnd)                    //构造数字导航区
        {
            if($pageStart == $this->_currentPage)
            {
                $navigation .= "<strong>$pageStart</strong>".$this->_splitString;
            }
            else
            {
                $navigation .= '<a href="'.$this->createHref($pageStart)."\">$pageStart</a>".$this->_splitString;
            }
            $pageStart++;
        }
        if($this->_currentPage != $this->_pageCount)    //下一页导航
        {
            $navigation .= ' <a href="'.$this->createHref($this->_currentPage+1)."\">$this->_nextPageString</a> ";
        }
        if($pageCote < $pageCoteCount-1)                //未页导航
        {
            $navigation .= '<a href="'.$this->createHref($this->_pageCount)."\">$this->_lastPageString</a> ";
        }
        //添加直接导航框
        //$navigation .= '<input type="text" size="3" onkeydown="if(event.keyCode==13){window.location=\' ';
        //$navigation .= $this->createHref().'\'+this.value;return false;}" />';
        //2008年8月27号补充输入非正确页码后出现的错误——begin
        $navigation .= '  <select onchange="window.location=\' '.$this->createHref().'=\'+this.options[this.selectedIndex].value;">';
        for ($i=1;$i<=$this->_pageCount;$i++){
                if ($this->getCurrentPage()==$i){
                        $selected = "selected";
                }
                else {
                        $selected = "";
                }
                $navigation .= '<option value='.$i.' '.$selected.'>'.$i.'</option>';
        }
        $navigation .= '</select>';
        //2008年8月27号补充输入非正确页码后出现的错误——end
        $navigation .= "</div>";
        return $navigation;
    }

    /**
     * 取得导航栏显示导航总页数
     *
     * @return int 导航栏显示导航总页数
     */
    public function getNavigationItemCount()
    {
        return $this->_navigationItemCount;
    }

    /**
     * 设置导航栏显示导航总页数
     *
     * @param  int $navigationCount:导航栏显示导航总页数
     */
    public function setNavigationItemCoun($navigationCount)
    {
        if(is_numeric($navigationCount))
        {
            $this->_navigationItemCount = $navigationCount;
        }
    }

    /**
     * 设置首页显示字符
     * @param string $firstPageString 首页显示字符
     */
    public function setFirstPageString($firstPageString)
    {
        $this->_firstPageString = $firstPageString;
    }

    /**
     * 设置上一页导航显示字符
     * @param string $previousPageString:上一页显示字符
     */
    public function setPreviousPageString($previousPageString)
    {
        $this->_previousPageString = $previousPageString;
    }

    /**
     * 设置下一页导航显示字符
     * @param string $nextPageString:下一页显示字符
     */
    public function setNextPageString($nextPageString)
    {
        $this->_nextPageString = $nextPageString;
    }

    /**
     * 设置未页导航显示字符
     * @param string $nextPageString:未页显示字符
     */
    public function setLastPageString($lastPageString)
    {
        $this->_lastPageString = $lastPageString;
    }

    /**
     * 设置导航字符显示位置
     * @param string $align:导航位置
     */
    public function setAlign($align)
    {
        $align = strtolower($align);
        if($align == "center")
        {
            $this->_align = "center";
        }elseif($align == "right")
        {
            $this->_align = "right";
        }else
        {
            $this->_align = "left";
        }
    }
    /**
     * 设置页面参数名称
     * @param string $pageParamName:页面参数名称
     */
    public function setPageParamName($pageParamName)
    {
        $this->_PageParaName = $pageParamName;
    }

    /**
     * 获取页面参数名称
     * @return string 页面参数名称
     */
    public function getPageParamName()
    {
        return $this->_PageParaName;
    }

    /**
     * 生成导航链接地址
     * @param int $targetPage:导航页
     * @return string 链接目标地址
     */
    private function createHref($targetPage = null)
    {
        //$params = $this->_front->getRequest()->getParams();
		$params = $_GET;
        //$targetUrl = $this->_front->getBaseUrl()."/$module/$controller/$action";
		$targetUrl = $_SERVER['PHP_SELF'] . '?';
		//print_r($_SERVER);exit;
		
        foreach ($params as $key => $value)
        {
			if ($key != 'page' ) {
				$targetUrl .= "&$key=$value";
			}
        }
		
        if(isset($targetPage)){          //指定目标页
            $targetUrl .= "&$this->_PageParaName=$targetPage";
		}else{
            $targetUrl .= "&$this->_PageParaName";
		}
        return $targetUrl;
    }
}