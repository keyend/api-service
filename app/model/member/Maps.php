<?php
namespace app\model\member;
/**
 * 会员结构
 * @version 1.0.0
 */
use app\Model;
use app\model\system\Ip;
use think\facade\Db;

class Maps extends Model
{
    protected $name = 'member_maps';

    /**
     * 获取结构
     *
     * @param array $filter
     * @return string
     */
    public function getMaps($filter = [])
    {
        $res = $this->where($filter)->find();
        if (empty($res)) {
            $res = array_column($filter, 2, 0);
            $this->insertGetId($res);
            return $this->where($filter)->find();
        }
        return $res;
    }

    /**
     * 向上递减、递增
     *
     * @param integer $parent_id
     * @param integer $value
     * @param array $maps
     * @return void
     */
    public function ascending_decrement($parent_id = 0, $value = 1, $maps = [])
    {
        while($parent_id != 0) {
            $parent = Member::where('member_id', '=', $parent_id)->field('member_id,parent_id,companion')->find();
            $parent->companion = Db::raw($value > 0 ? "companion + {$value}" : "companion {$value}");
            $parent->save();
            $parent->dispatchMaps($maps, $value);
            $parent_id = $parent['parent_id'];
        }
    }

    /**
     * 向下聚合
     *
     * @param integer $member_id
     * @return array
     */
    public function aggregation_downmaps($member_id = 0)
    {
        $maps = [];
        Db::query('SET GLOBAL group_concat_max_len=102400;');
        Db::query('SET SESSION group_concat_max_len=102400;');
        $ids = Member::where('parent_id', $member_id)->value("GROUP_CONCAT(`member_id`)");
        $maps[] = $ids;
        while(!empty($ids)) {
            $ids = Member::where('parent_id', 'IN', $ids)->value("GROUP_CONCAT(`member_id`)");
            if (!empty($ids)) {
                $maps[] = $ids;
            }
        }
        $maps = array_filter(explode(",", implode(",", $maps)));
        return $maps;
    }

    /**
     * 获取跌代
     *
     * @return void
     */
    private function getIterator($maps)
    {
        foreach($maps as $id) {
            yield $id;
        }
    }

    /**
     * 清空结构
     *
     * @param array $filter
     * @return void
     */
    private function clear($filter = [])
    {
        $this->where($filter)->update(['maps' => null]);
    }

    /**
     * 重建结构
     *
     * @return void
     */
    public function rebuild()
    {
        $this->clear([ ['member_id', 'IN', $this->getAttr('maps')] ]);

        $member_id = $this->getAttr('member_id');
        $memberNewMaps = $this->aggregation_downmaps($member_id);

        $this->maps = implode(',', $memberNewMaps);
        $this->companion = count($memberNewMaps);
        $this->save();

        foreach($this->getIterator($memberNewMaps) as $children_id) {
            $currentMaps = $this->aggregation_downmaps($children_id);
            $memberMaps = $this->getMaps([ ['member_id', '=', $children_id] ]);
            $memberMaps->maps = implode(',', $currentMaps);
            $memberMaps->companion = count($currentMaps);
            $memberMaps->save();
        }

        return $memberNewMaps;
    }
}