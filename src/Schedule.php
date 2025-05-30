<?php
namespace TaskSchedule;

class Systems
{
    public function create($data)
    {
        if (class_exists(\App\Models\TaskScheduling::class)) {
            $scheduleModel = new \App\Models\TaskScheduling();
        } else {
            \Log::error("Class TaskScheduling does not exist.");
            return false;
        }
        if (is_object($data)) {
            $data = $data->data;
        }
        $input = \Arr::only($data, ['relate_type', 'relate_id', 'schedule_name','type','schedule_time', 'data']);
        $validator = \Validator::make($input, [ 
            'relate_type' => 'required',
            'relate_id' => 'required',
            'schedule_name' => 'required',
            'type' => 'required',
        ]);
        if ($validator->stopOnFirstFailure()->fails()) {
            return false;
        }
        $check = $scheduleModel->all(['relate_type' => $input['relate_type'], 'relate_id' => $input['relate_id']]);
        if($check->isEmpty()) {
            if (empty($input['schedule_time'])) {
                $input['schedule_time'] = time();
            }
            $input['schedule_time'] = (int) $input['schedule_time'];
            $scheduleModel->create($input);
        }
        return true;
    }
}