<?php
////////////////////////////////////////////////////////
//Scratchpadを活用してAmazonMWSで出荷通知をPHPで送信させる
////////////////////////////////////////////////////////
require_once('.config.inc'); //AmazonMWSの設定ファイルを読み込む

//==========
// 必須
//==========

$order_id = "123-1234567-1234567"; //注文番号
$ship_date = date(DATE_ATOM); //出荷日（XML送信用にatom規格の表示を出力にする）

//==========
// 推奨 
//==========

$carrier_code = "Other" //配送業者コード
$carrier_name = "ヤマト運輸" //配送業者名
$tracking_number "123456789012" //お問い合わせ伝票番号（代引の際必須）

//==========
// 任意
//==========

$order_item_id = "12345678901234" //注文商品番号
$quantity = "1" //出荷数
$ship_method "メール便"//配送方法
$cod_collection_method = "DirectPayment" //代金引換

//AmazonMWSに出荷通知フィードを送信してレスポンスを取得する関数
function sendShippingMailAmazon($order_id,$ship_date,$carrier_code,$carrier_name,$tracking_number)
{

	try {
		$serviceUrl = "https://mws.amazonservices.jp";
		$config = array (
			'ServiceURL' => $serviceUrl,
			'ProxyHost' => null,
			'ProxyPort' => -1,
			'MaxErrorRetry' => 3,
		);

		$service = new MarketplaceWebService_Client(
			AWS_ACCESS_KEY_ID,
			AWS_SECRET_ACCESS_KEY,
			$config,
			APPLICATION_NAME,
			APPLICATION_VERSION);

		//送信するXML形式フィード
		$feed = <<<EOD
<?xml version="1.0" encoding="UTF-8"?>
<AmazonEnvelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="amzn-envelope.xsd">
    <Header>
        <DocumentVersion>1.01</DocumentVersion>
        <MerchantIdentifier/>
    </Header>
    <MessageType>OrderFulfillment</MessageType>
    <Message>
        <MessageID>1</MessageID>
        <OperationType>Update</OperationType>
        <OrderFulfillment>
            <AmazonOrderID>{$order_id}</AmazonOrderID>
            <FulfillmentDate>{$ship_date}</FulfillmentDate>
			<FulfillmentData>
				<CarrierCode>{$carrier_code}</CarrierCode>
                <CarrierName>{$carrier_name}</CarrierName>
                <ShipperTrackingNumber>{$tracking_number}</ShipperTrackingNumber>
            </FulfillmentData>
        </OrderFulfillment>
    </Message>
</AmazonEnvelope>
EOD;
		
		$marketplaceIdArray = array("Id" => array(MARKETPLACE_ID));

		//フィード送信
		$feedHandle = @fopen('php://temp', 'rw+');
		fwrite($feedHandle, $feed);
		rewind($feedHandle);

		$request = new MarketplaceWebService_Model_SubmitFeedRequest();
		$request->setMerchant(MERCHANT_ID);
		$request->setMarketplaceIdList($marketplaceIdArray);
		$request->setFeedType('_POST_ORDER_FULFILLMENT_DATA_');
		$request->setContentMd5(base64_encode(md5(stream_get_contents($feedHandle), true)));
		rewind($feedHandle);
		$request->setPurgeAndReplace(false);
		$request->setFeedContent($feedHandle);
		rewind($feedHandle);
		
		$ret = $this->invokeSubmitFeed($service, $request);
		@fclose($feedHandle);

		//invokeSubmitFeedの例外処理
		if($ret == 'ERROR1'){
			return $ret;
		}

		//送信したフィードとFeedSubmissionIdを記録しておく
		$fp = fopen("shipmailsubmitfeedlog.txt", "a" );
		$send_time = date( "Y-m-d H:i:s" ).'【以上の出荷通知フィードをAmazonMWSに送信しました】';
		fputs($fp, $feed."\r\nFeedSubmissionId：".$ret."\r\n".$send_time."\r\n");
		fclose($fp);

		$FeedSubmissionId = $ret; //送信したフィードID

		//送信したフィードの処理状態が_DONE_になるまで待機
		$request = new MarketplaceWebService_Model_GetFeedSubmissionListRequest();
		$request->setMerchant(MERCHANT_ID);

		$statusList = new MarketplaceWebService_Model_StatusList();
		$request->setFeedProcessingStatusList($statusList->withStatus('_DONE_')); //_DONE_のみ

		sleep(20);
		$hoge = 1;

		while($hoge < 5){
			$ret = $this->invokeGetFeedSubmissionList($service, $request, $FeedSubmissionId); //_DONE_かどうか

			if($ret == 1){
				break; //フィード処理済み
			}

			sleep(20);
			$hoge += 1;
		}
		//invokeGetFeedSubmissionListの例外処理
		if($ret == 0){
			$ret = 'ERROR2';
			return $ret;
		}

		//出荷通知の成否を確かめる
		$file_name = "shipmailfeedresultlog.txt"; 

		$request = new MarketplaceWebService_Model_GetFeedSubmissionResultRequest();
		$request->setMerchant(MERCHANT_ID);
		$request->setFeedSubmissionId($FeedSubmissionId);
		$request->setFeedSubmissionResult(@fopen($file_name, 'rw+'));　//最後に送信したフィードの結果を保存する

		$ret = $this->invokeGetFeedSubmissionResult($service, $request);

		@fclose($file_name);

		//invokeGetFeedSubmissionResultの例外処理
		if($ret == 'ERROR3'){
			return $ret;
		}

		//保存したXMLファイルから成否を判断する
		$xml = simplexml_load_file('shipmailfeedresultlog.txt');
		$xml = json_decode(json_encode($xml), true);		

		if($xml['Message']['ProcessingReport']['ProcessingSummary']['MessagesSuccessful') == 1){
			$ret = ''; //処理成功
		}
		else {
			$ret = 'ERROR4'; //処理失敗			
		}
		return $ret;
	}
	//例外処理
	catch (Exception $ex) {
		$ret = 'ERROR0';
		return $ret;
	}
}

//====================================
// 以下Amazonライブラリのメソッド
//====================================

function invokeSubmitFeed(MarketplaceWebService_Interface $service, $request)
{
	try {
		$response = $service->submitFeed($request);
		
		//echo ("Service Response\n");
		//echo ("=============================================================================\n");
		
		//echo("        SubmitFeedResponse\n");
		if ($response->isSetSubmitFeedResult()) {
			//echo("            SubmitFeedResult\n");
			$submitFeedResult = $response->getSubmitFeedResult();
			if ($submitFeedResult->isSetFeedSubmissionInfo()) {
				//echo("                FeedSubmissionInfo\n");
				$feedSubmissionInfo = $submitFeedResult->getFeedSubmissionInfo();
				if ($feedSubmissionInfo->isSetFeedSubmissionId())
				{
					//echo("                    FeedSubmissionId\n");
					//echo("                        " . $feedSubmissionInfo->getFeedSubmissionId() . "\n");
					$ret = $feedSubmissionInfo->getFeedSubmissionId(); //FeedSubmissionInfoからFeedSubmissionIdを取得
				}
				if ($feedSubmissionInfo->isSetFeedType())
				{
					//echo("                    FeedType\n");
					//echo("                        " . $feedSubmissionInfo->getFeedType() . "\n");
				}
				if ($feedSubmissionInfo->isSetSubmittedDate())
				{
					//echo("                    SubmittedDate\n");
					//echo("                        " . $feedSubmissionInfo->getSubmittedDate()->format(DATE_FORMAT) . "\n");
				}
				if ($feedSubmissionInfo->isSetFeedProcessingStatus())
				{
					//echo("                    FeedProcessingStatus\n");
					//echo("                        " . $feedSubmissionInfo->getFeedProcessingStatus() . "\n");
				}
				if ($feedSubmissionInfo->isSetStartedProcessingDate())
				{
					//echo("                    StartedProcessingDate\n");
					//echo("                        " . $feedSubmissionInfo->getStartedProcessingDate()->format(DATE_FORMAT) . "\n");
				}
				if ($feedSubmissionInfo->isSetCompletedProcessingDate())
				{
					//echo("                    CompletedProcessingDate\n");
					//echo("                        " . $feedSubmissionInfo->getCompletedProcessingDate()->format(DATE_FORMAT) . "\n");
				}
			}
		}
		if ($response->isSetResponseMetadata()) {
			//echo("            ResponseMetadata\n");
			$responseMetadata = $response->getResponseMetadata();
			if ($responseMetadata->isSetRequestId())
			{
				//echo("                RequestId\n");
				//echo("                    " . $responseMetadata->getRequestId() . "\n");
			}
		}
		
		//echo("            ResponseHeaderMetadata: " . $response->getResponseHeaderMetadata() . "\n");
	}
	catch (MarketplaceWebService_Exception $ex) {
		//echo("Caught Exception: " . $ex->getMessage() . "\n");
		//echo("Response Status Code: " . $ex->getStatusCode() . "\n");
		//echo("Error Code: " . $ex->getErrorCode() . "\n");
		//echo("Error Type: " . $ex->getErrorType() . "\n");
		//echo("Request ID: " . $ex->getRequestId() . "\n");
		//echo("XML: " . $ex->getXML() . "\n");
		//echo("ResponseHeaderMetadata: " . $ex->getResponseHeaderMetadata() . "\n");
		
		$ret = 'ERROR1';
	}
	return $ret;
}

function invokeGetFeedSubmissionList(MarketplaceWebService_Interface $service, $request, $FeedSubmissionId)
{
	try {
		$response = $service->getFeedSubmissionList($request);
		$done = 0;
		
		//echo ("Service Response\n");
		//echo ("=============================================================================\n");
		
		//echo("        GetFeedSubmissionListResponse\n");
		if ($response->isSetGetFeedSubmissionListResult()) {
			//echo("            GetFeedSubmissionListResult\n");
			$getFeedSubmissionListResult = $response->getGetFeedSubmissionListResult();
			if ($getFeedSubmissionListResult->isSetNextToken())
			{
				//echo("                NextToken\n");
				//echo("                    " . $getFeedSubmissionListResult->getNextToken() . "\n");
			}
			if ($getFeedSubmissionListResult->isSetHasNext())
			{
				//echo("                HasNext\n");
				//echo("                    " . $getFeedSubmissionListResult->getHasNext() . "\n");
			}
			$feedSubmissionInfoList = $getFeedSubmissionListResult->getFeedSubmissionInfoList();
			foreach ($feedSubmissionInfoList as $feedSubmissionInfo) {
				//echo("                FeedSubmissionInfo\n");
				if ($feedSubmissionInfo->isSetFeedSubmissionId())
				{
					//echo("                    FeedSubmissionId\n");
					//echo("                        " . $feedSubmissionInfo->getFeedSubmissionId() . "\n");
					
					if($feedSubmissionInfo->getFeedSubmissionId() == $FeedSubmissionId){
						$done = 1;
					}
					else{
					}
				}
				if ($feedSubmissionInfo->isSetFeedType())
				{
					//echo("                    FeedType\n");
					//echo("                        " . $feedSubmissionInfo->getFeedType() . "\n");
				}
				if ($feedSubmissionInfo->isSetSubmittedDate())
				{
					//echo("                    SubmittedDate\n");
					//echo("                        " . $feedSubmissionInfo->getSubmittedDate()->format(DATE_FORMAT) . "\n");
				}
				if ($feedSubmissionInfo->isSetFeedProcessingStatus())
				{
					//echo("                    FeedProcessingStatus\n");
					//echo("                        " . $feedSubmissionInfo->getFeedProcessingStatus() . "\n");
				}
				if ($feedSubmissionInfo->isSetStartedProcessingDate())
				{
					//echo("                    StartedProcessingDate\n");
					//echo("                        " . $feedSubmissionInfo->getStartedProcessingDate()->format(DATE_FORMAT) . "\n");
				}
				if ($feedSubmissionInfo->isSetCompletedProcessingDate())
				{
					//echo("                    CompletedProcessingDate\n");
					//echo("                        " . $feedSubmissionInfo->getCompletedProcessingDate()->format(DATE_FORMAT) . "\n");
				}
			}
		}
		if ($response->isSetResponseMetadata()) {
			//echo("            ResponseMetadata\n");
			$responseMetadata = $response->getResponseMetadata();
			if ($responseMetadata->isSetRequestId())
			{
				//echo("                RequestId\n");
				//echo("                    " . $responseMetadata->getRequestId() . "\n");
			}
		}
		
		//echo("            ResponseHeaderMetadata: " . $response->getResponseHeaderMetadata() . "\n");
		if($done == 1){
			$ret = 1;
		}
		else if($done == 0){
			$ret = 0;
		}
		return $ret;
	}
	catch (MarketplaceWebService_Exception $ex) {
		//echo("Caught Exception: " . $ex->getMessage() . "\n");
		//echo("Response Status Code: " . $ex->getStatusCode() . "\n");
		//echo("Error Code: " . $ex->getErrorCode() . "\n");
		//echo("Error Type: " . $ex->getErrorType() . "\n");
		//echo("Request ID: " . $ex->getRequestId() . "\n");
		//echo("XML: " . $ex->getXML() . "\n");
		//echo("ResponseHeaderMetadata: " . $ex->getResponseHeaderMetadata() . "\n");
		$ret = 'ERROR2';
		return $ret;
	}
}

function invokeGetFeedSubmissionResult(MarketplaceWebService_Interface $service, $request)
{
	try {
		$response = $service->getFeedSubmissionResult($request);
		
		//echo ("Service Response\n");
		//echo ("=============================================================================\n");
		
		//echo("        GetFeedSubmissionResultResponse\n");
		if ($response->isSetGetFeedSubmissionResultResult()) {
			$getFeedSubmissionResultResult = $response->getGetFeedSubmissionResultResult();
			//echo ("            GetFeedSubmissionResult");
			
			if ($getFeedSubmissionResultResult->isSetContentMd5()) {
				//echo ("                ContentMd5");
				//echo ("                " . $getFeedSubmissionResultResult->getContentMd5() . "\n");
			}
		}
		if ($response->isSetResponseMetadata()) {
			//echo("            ResponseMetadata\n");
			$responseMetadata = $response->getResponseMetadata();
			if ($responseMetadata->isSetRequestId())
			{
				//echo("                RequestId\n");
				//echo("                    " . $responseMetadata->getRequestId() . "\n");
			}
		}
		
		//echo("            ResponseHeaderMetadata: " . $response->getResponseHeaderMetadata() . "\n");
		$ret = '';
	}
	catch (MarketplaceWebService_Exception $ex) {
		//echo("Caught Exception: " . $ex->getMessage() . "\n");
		//echo("Response Status Code: " . $ex->getStatusCode() . "\n");
		//echo("Error Code: " . $ex->getErrorCode() . "\n");
		//echo("Error Type: " . $ex->getErrorType() . "\n");
		//echo("Request ID: " . $ex->getRequestId() . "\n");
		//echo("XML: " . $ex->getXML() . "\n");
		//echo("ResponseHeaderMetadata: " . $ex->getResponseHeaderMetadata() . "\n");
		$ret = 'ERROR3';
	}
	return $ret;
}
