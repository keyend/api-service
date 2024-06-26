<?php
namespace app\addons\stat\admin\controller;
/**
 * 仪表盘
 * 
 * @version 1.0.0
 */
use app\controller\admin\Controller;
use app\addons\stat\admin\model\MemberLabel;
use app\addons\stat\admin\model\Order;

class Dashboard extends Controller
{
    /**
     * 仪表盘
     *
     * @return void
     */
    public function index()
    {
        if (request()->isAjax()) {
            $patch = input("patch", "Dashboard");
            $method = "get" . ucfirst($patch);
            $res = $this->$method();
            return $this->success($res);
        } else {
            return $this->fetch();
        }
    }

    /**
     * 数据概览
     *
     * @return void
     */
    private function getDashboard()
    {
        return (new Order())->getDashboardData();
    }

    /**
     * 柱形图
     *
     * @return void
     */
    private function getShape()
    {
        return [
            'customer' => ["Alix", "Karen Roberts", "七月物语", "犹豫的飞鸟", "Thomas Jackson", "范柔静"],
            'list' => [
                [
                    "name" => "05月份",
                    "type" => "bar",
                    "data" => [
                        212, 46, 0,0,0,0
                    ]
                ],
                [
                    "name" => "06月份",
                    "type" => "bar",
                    "data" => [
                        44, 31, 0,0,0,0
                    ]
                ]
            ]
        ];
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    private function getPie()
    {
        return [
            'customer' => ["Alix", "Karen Roberts", "七月物语", "犹豫的飞鸟", "Thomas Jackson", "范柔静"],
            'list' => [
                [
                    "name" => "05月份",
                    "value" => 212
                ],
                [
                    "name" => "Karen Roberts",
                    "value" => 46
                ],
                [
                    "name" => "七月物语",
                    "value" => 0
                ],
                [
                    "name" => "犹豫的飞鸟",
                    "value" => 0
                ],
                [
                    "name" => "Thomas Jackson",
                    "value" => 0
                ],
                [
                    "name" => "范柔静",
                    "value" => 0
                ]
            ]
        ];
    }

    /**
     * 业务时间线
     *
     * @return void
     */
    private function getOrder()
    {
        return [
            'names' => ["订单数量", "订单金额"],
            'dates' => ["2024/05/04","2024/05/05","2024/05/06","2024/05/07","2024/05/08","2024/05/09","2024/05/10","2024/05/11","2024/05/12","2024/05/13","2024/05/14","2024/05/15","2024/05/16","2024/05/17","2024/05/18","2024/05/19","2024/05/20","2024/05/21","2024/05/22","2024/05/23","2024/05/24","2024/05/25","2024/05/26","2024/05/27","2024/05/28","2024/05/29","2024/05/30","2024/05/31","2024/06/01","2024/06/02","2024/06/03"],
            'list' => [
                [
                    "name" => "订单数量",
                    "type" => "line",
                    "data" => [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],
                    "markPoint" => [
                        "data" => [
                            [
                                "type" => "max",
                                "name" => "最大值"
                            ],
                            [
                                "type" => "min",
                                "name" => "最小值"
                            ]
                        ]
                    ],
                    "itemStyle" => [
                        "normal" => [
                            "color" => null
                        ]
                    ]
                ],
                [
                    "name" => "订单金额",
                    "type" => "line",
                    "data" => [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],
                    "markPoint" => [
                        "data" => [
                            [
                                "type" => "max",
                                "name" => "最大值"
                            ],
                            [
                                "type" => "min",
                                "name" => "最小值"
                            ]
                        ]
                    ],
                    "itemStyle" => [
                        "normal" => [
                            "color" => null
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * 配送时间线
     *
     * @return void
     */
    private function getDelivery()
    {
        return [
            'names' => ["配送数量", "配送金额"],
            'dates' => ["2024/05/04","2024/05/05","2024/05/06","2024/05/07","2024/05/08","2024/05/09","2024/05/10","2024/05/11","2024/05/12","2024/05/13","2024/05/14","2024/05/15","2024/05/16","2024/05/17","2024/05/18","2024/05/19","2024/05/20","2024/05/21","2024/05/22","2024/05/23","2024/05/24","2024/05/25","2024/05/26","2024/05/27","2024/05/28","2024/05/29","2024/05/30","2024/05/31","2024/06/01","2024/06/02","2024/06/03"],
            'list' => [
                [
                    "name" => "配送数量",
                    "type" => "line",
                    "data" => [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],
                    "markPoint" => [
                        "data" => [
                            [
                                "type" => "max",
                                "name" => "最大值"
                            ],
                            [
                                "type" => "min",
                                "name" => "最小值"
                            ]
                        ]
                    ],
                    "itemStyle" => [
                        "normal" => [
                            "color" => null
                        ]
                    ]
                ],
                [
                    "name" => "配送金额",
                    "type" => "line",
                    "data" => [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],
                    "markPoint" => [
                        "data" => [
                            [
                                "type" => "max",
                                "name" => "最大值"
                            ],
                            [
                                "type" => "min",
                                "name" => "最小值"
                            ]
                        ]
                    ],
                    "itemStyle" => [
                        "normal" => [
                            "color" => null
                        ]
                    ]
                ]
            ]
        ];
    }
}