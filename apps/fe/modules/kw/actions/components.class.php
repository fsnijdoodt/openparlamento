<?php
/**
 * Created by JetBrains PhpStorm.
 * User: guglielmo
 * Date: 20/09/13
 * Time: 15:11
 * To change this template use File | Settings | File Templates.
 */

class KwComponents extends sfComponents
{
    public function executeWebtrekk_home(){}
    public function executeWebtrekk_list(){}
    public function executeWebtrekk_detail(){}
    public function executeWebtrekk_table(){}
    public function executeWebtrekk_forum(){}
    public function executeWebtrekk_searchresults()
    {
        $this->query = $this->getRequestParameter('query');
    }

}