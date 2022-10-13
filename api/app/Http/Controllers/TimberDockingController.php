<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;

class TimberDockingController extends Controller
{
    public function processJsonData(Request $request){

        $jsonData = json_decode($request->json);
        $outputJsonData = [];

        $balance = 0;
        $isValid = false;
        $errorReason = null;

        if(is_array($jsonData)){
            foreach($jsonData as $data){
                $transformations = $data->transformations;
                
                $runningBalance = 0;
                $partNums=array();
                $childPartsSize=array();

                foreach($transformations as $transformData){
                    $runningBalance = $runningBalance + ($transformData->qty * $transformData->size);
                    array_push($partNums, $transformData->partNum);
                    array_push($childPartsSize, $transformData->size);
                }
                //DATA VALIDATIONS
                $validatePNResult = $this->validatePartNums($partNums);
                $validateCPResult = $this->validateChildPartsSize($childPartsSize);

                if(!$validatePNResult){
                    $isValid = false;
                    $errorReason = "A source partNum that does not match the child partNums";
                } elseif(!$validateCPResult['isValid']){
                    $isValid = $validateCPResult['isValid'];
                    $errorReason = $validateCPResult['errorReason'];
                }
                else {
                    $isValid = true;
                    $errorReason = null;
                }

                //per transaction data
                $perTransactionData=[
                    "transaction"=>$data->transaction,
                    "transformation"=>$transformations,
                    "balance" => $runningBalance,
                    "isValid" => $isValid,
                    "errorReason" => $errorReason    
                ]; 

                //push to outputJsonData
                array_push($outputJsonData, $perTransactionData);
            }
            //return $jsonData;
            return [
                'success' => true,
                'message' => 'JSON data is processed succesfully.',
                'data' => $outputJsonData
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Invalid JSON input data detected.',
                'data' => $jsonData
            ];
        }
    }

    public function validatePartNums($partNums){
        if (count(array_flip($partNums)) === 1) {
            return true;
        } else {
            return false;
        }
    }

    public function validateChildPartsSize($childPartsSize){
        $isSizeValid = true;
        $isIncrementedValid = true;
        $isValid = true;
        $errorReason = null;

        sort($childPartsSize);
        
        
        $lastSize = 0;
        foreach($childPartsSize as $size){
            //validate size if valid
            if($isSizeValid && ($size < 3 || $size > 12)){
                $isSizeValid = false;
                $isValid = false;
                $errorReason = "Child part is shorter than 3 metres or longer than 12 metres";
            }

            //validate incremented value if > 0.3
            if($lastSize == 0){
                $lastSize = $size;
            } else {
                $incremented = $size - $lastSize;
                if($isIncrementedValid && $incremented < 0.3){
                    $isIncrementedValid = false;
                    $isValid = false;
                    $errorReason = "Child parts are not in increment within the range of 0.3.";
                }
                $lastSize = $size;
            }
        }

        return [
            'isValid'=>$isValid,
            'errorReason'=>$errorReason
        ];
    }
}
