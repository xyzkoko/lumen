<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
//use GuzzleHttp\Psr7\Request as GuzzRequest;
use Illuminate\Http\Request;

class AppController extends Controller
{
    public $client;

    function __construct()
    {
        $this->client = new Client(['base_uri' => env('OTA_URL'), 'timeout' => 2.0, 'http_errors' => false]);
    }

    /**
     * 轮询任务
     */
    public function task(Request $request)
    {
        $this->validate($request, [
            'serialNo' => 'required'
        ]);

        try {
            $result = $this->client->request('GET', '/api/ota/task?term_id=' . $request->get('serialNo'));
            $result = json_decode($result->getBody(), true);
            $data = [
                'error_code' => isset($result['code']) ? $result['code'] : $result['status_code'],
                'error_msg' => isset($result['msg']) ? $result['msg'] : $result['message'],
                'data' => []
            ];
            if ($data['error_code'] == '00000') {
                for($i=0;$i<count($result['data']);$i++){
                    $result['data'][$i]['host'] = "http://".$result['data'][$i]['host'];
                }
                $data['data'] = $result['data'];
            }

            return $data;
        } catch (ClientException $e) {
            return [
                'error_code' => '404',
                'error_msg' => '内部网络请求失败',
                'data' => []
            ];
        }

    }

    /**
     * 保存任务执行结果
     */
    public function taskResult(Request $request)
    {
        $this->validate($request, [
            'serialNo' => 'required',
            'task_id' => 'required',
            'result' => ['required', 'regex:/^(0|1|2|3|4)$/']
        ]);

        try {
            $result = $this->client->request('POST', '/api/ota/task/result',
                ['json' => [
                    'task_id' => $request->get('task_id'),
                    'result' => $request->get('result'),
                    'term_id' => $request->get('serialNo')
                ]]);
            $result = json_decode($result->getBody(), true);
            return [
                'error_code' => isset($result['code']) ? $result['code'] : $result['status_code'],
                'error_msg' => isset($result['msg']) ? $result['msg'] : $result['message']
            ];
        } catch (ClientException $e) {
            return [
                'error_code' => '404',
                'error_msg' => '内部网络请求失败'
            ];
        }

    }

    /**
     * 获取已安装应用名
     */
    public function appList(Request $request)
    {
        $this->validate($request, [
            'serialNo' => 'required'
        ]);

        try {
            $result = $this->client->request('GET', '/api/ota/task/app_list?term_id=' . $request->get('serialNo'));
            $result = json_decode($result->getBody(), true);

            $data = [
                'error_code' => isset($result['code']) ? $result['code'] : $result['status_code'],
                'error_msg' => isset($result['msg']) ? $result['msg'] : $result['message'],
                'data' => []
            ];

            if ($data['error_code'] == '00000') {
                $data['data'] = $result['data'];
            }

            return $data;

        } catch (ClientException $e) {
            return [
                'error_code' => '404',
                'error_msg' => '内部网络请求失败',
                'data' => []
            ];
        }

    }

    /**
     *保存备份信息
     */
    public function backup(Request $request)
    {
        $this->validate($request, [
            'file' => 'required|file',
            'serialNo' => 'required'
        ]);

        $file = $request->file('file');
        if ($file->getClientMimeType() != 'application/octet-stream') {
            return [
                'error_code' => '10001',
                'error_msg' => '文件格式必须为zip'
            ];
        }
        $flog = copy($file->getRealPath(), base_path() . '/public/data/' . $request->get('serialNo') . '.zip');
        if (!$flog) {
            return [
                'error_code' => '10002',
                'error_msg' => '保存文件失败'
            ];
        } else {
            try {
                $result = $this->client->request('POST', '/api/ota/backup',
                    ['json' => ['term_id' => $request->get('serialNo'), 'path' => '/data/' . $request->get('serialNo') . '.zip']]);
                $result = json_decode($result->getBody(), true);

                return [
                    'error_code' => isset($result['code']) ? $result['code'] : $result['status_code'],
                    'error_msg' => isset($result['msg']) ? $result['msg'] : $result['message']
                ];
            } catch (ClientException $e) {
                return [
                    'error_code' => '404',
                    'error_msg' => '内部网络请求失败'
                ];
            }

        }


    }

    /**
     * 返回备份文件地址
     */
    public function getBackup(Request $request)
    {
        $this->validate($request, [
            'serialNo' => 'required'
        ]);

        try {
            $result = $this->client->request('GET', '/api/ota/backup?term_id=' . $request->get('serialNo'));
            $result = json_decode($result->getBody(), true);

        } catch (ClientException $e) {
            return [
                'error_code' => '404',
                'error_msg' => '内部网络请求失败',
                'data' => []
            ];
        }

        if (isset($result['code']) && $result['code'] == '00000') {
            if (isset($result['path']) && !file_exists(base_path() . '/public/' . $result['path'])) {
                return [
                    'error_code' => '10003',
                    'error_msg' => '备份文件不存在',
                    'data' => []
                ];
            } else {
                return [
                    'error_code' => '00000',
                    'error_msg' => '操作成功',
                    'data' => [
                        'host' => env('APP_URL'),
                        'path' => $result['data']['path']
                    ]
                ];
            }
        } else {
            return [
                'error_code' => $result['status_code'],
                'error_msg' => $result['message'],
                'data' => []
            ];
        }

    }
}
