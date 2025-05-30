<?php

namespace TaskSchedule;

use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

class ScheduleServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            $schedule->call(function () {
                if (!class_exists(\App\Models\TaskScheduling::class)) {
                    \Log::error("Class TaskScheduling does not exist.");
                    return;
                }

                $model = new \App\Models\TaskScheduling();

                $now = time();
                $data = $model->all(['schedule_time' => ['lte' => $now]], ['limit' => 1000]);

                foreach ($data as $task) {
                    if (!empty($task['type']) && !empty($task['schedule_name'])) {
                        try {
                            $micro = new \Microservices\models\Microservices();
                            if ($task['type'] === 'event') {
                                $event = $task['schedule_name'] ?? '';
                                if (!empty($event)) {
                                    $micro->event()->BusEvent($event, $task);
                                }
                            } elseif ($task['type'] === 'job') {
                                $jobClass = $task['schedule_name'] ?? '';
                                if (!empty($jobClass)) {
                                    $micro->job()->BusJob($jobClass, $task)->onQueue(config('app.service_code'));
                                }
                            }

                            // Sau khi xử lý xong, xóa task
                            $model->delete($task['_id']);
                        } catch (\Exception $e) {
                            \Log::error('Error running scheduled task', [
                                'scheduele' => $task['_id'] ?? '',
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString()
                            ]);
                        }
                    }
                }
            })->everyFiveMinutes();
        });
    }


    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
       //
    }

    
}
