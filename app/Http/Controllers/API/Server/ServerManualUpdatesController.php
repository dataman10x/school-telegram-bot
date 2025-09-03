<?php
namespace App\Http\Controllers\API\Server;


use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Classes\Ability;
use App\Classes\Parser;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exceptions\SmartResponse;
use App\Models\DialogMessages;
use App\Models\OpinionPolls;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ServerManualUpdatesController extends Controller
{
    private $parser;

    public function __construct()
    {
        $this->parser = new Parser;
    }

    public function update(Request $request)
    {
        $feedback = new SmartResponse();
        $responsetype = $_POST["type"];
        $responsetype = !is_null($responsetype)? $responsetype: null;

        // verify if autorized
        $secret = MASTER_SECRET;
        $param = null;
        $param = htmlspecialchars($_POST["key"]);
        $res = [];

        if($secret !== $param) {
            $title = "You are not allowed to update schema";
            array_push($res, $title);
            return $feedback->json($res, false);
        }

        try {
            if(env('ADMIN_UPDATE_ACTIVE', false)) {
                $reportArr = [];

                array_push($reportArr, $this->poll());
                array_push($reportArr, $this->faq());

                 $countReports = count($reportArr);
                 $reports = implode("; ", $reportArr);
                $message = "Database Update Reports ($countReports): $reports";
            } else {
                $message = 'Database update failed. Admin Update is inactive.';
            }
            if(!is_null($responsetype)) {
                return $feedback->exec('', $message, true);
            } else {
                $feedback->alertMessage($message,'success');
                return redirect()->back();
            }
        } catch (\Throwable $th) {
            $message = 'Something went wrong! Database was not modified.';
            $message = json_encode($th);
            if(!is_null($responsetype)) {
                return $feedback->exec('', $message, false);
            } else {
                $feedback->alertMessage($message,'error', true);
                return redirect()->back();
            }
        }
    }

    private function poll()
    {
        $name = "Opinion Poll";
        $res = '';
        $options = [
            "Pay Weekly",
            "Pay Monthly",
            "Pay Yearly",
            "Pay Once"
        ];

        $startAt = $this->parser->dateNow();
        $endAt = $this->parser->addDays(5);

        try {
            $save = new OpinionPolls;
            $save->label = "Billing Poll";
            $save->detail = "This Poll will sample the best options for paying to own a Bot for listing businesses.";
            $save->question = "How do you wish to pay in order to own a Business listing Bot like me?";
            $save->options = $options;
            $save->start_at = $startAt;
            $save->end_at = $endAt;

            if($save->save()) {
                $res = "$name was updated";
            }
            else {
                $res = "$name failed";
            }
        } catch (\Throwable $th) {
            $res = "Error in $name";
            Log::error($th);
        }

        return $res;
    }

    private function faq()
    {
        $name = "Dialog Messages (FAQ)";
        $res = '';
        $dataArr = config('telegram.faq');
        $dataCount = count($dataArr);
        $counter = 0;

        try {
            DialogMessages::truncate();

            foreach ($dataArr as $val) {
                $save = new DialogMessages;
                $save->keywords = $val['keywords'];
                $save->detail = $val['value'];

                if($save->save()) {
                    $counter++;
                }
            }

            if($counter > 0) {
                $res = "$name was updated with $counter out of $dataCount records";
            }
            else {
                $res = "$name failed";
            }
        } catch (\Throwable $th) {
            $res = "Error in $name";
            Log::error($th);
        }

        return $res;
    }
}
