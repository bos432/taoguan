<?php

namespace app\merchant\controller\inspection;
use app\common\controller\BaseController;
use app\common\service\inspection\InspectionService;
use hg\apidoc\annotation as Apidoc;
/**
 * @Apidoc\Title("检测机构管理")
 * @Apidoc\Group("inspection")
 * @Apidoc\Sort("250")
 */
class Inspection extends BaseController
{
    /**
     * @Apidoc\Title("查询检测机构")
     * @Apidoc\Query(ref="pagingQuery")
     * @Apidoc\Query(ref="sortQuery")
     * @Apidoc\Query(ref="searchQuery")
     * @Apidoc\Query(ref="dateQuery")
     * @Apidoc\Returned(ref="expsReturn")
     * @Apidoc\Returned(ref="pagingReturn")
     * @Apidoc\Returned("list", type="array", desc="查询检测机构", children={
     *   @Apidoc\Returned(ref="app\common\model\InspectionModel", field="id,title,content,is_disable,create_time,update_time,username,password,region_id,address,phone,auth_state,auth_msg,sort,remark")
     * })
     */
    public function select()
    {
        $where = $this->where(where_delete());
        $where[] = ['is_disable','=',0];
        $data = InspectionService::list($where,0,0, $this->order(),'id as value,title as label,is_disable as disable,phone,address');
        return success($data);
    }
}
