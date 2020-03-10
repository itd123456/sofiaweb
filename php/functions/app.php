<?php
require_once('class.php');


$data = array();

foreach($_POST as $k => $v){
    $data[$k] = $v;
}

$class = new sofia(
    $data
);


$result = $class->payment($data);

if($result){
    require_once('tcpdf_include.php');

// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false, true);

// set document information


// set default header data
$pdf->setHeaderFont(array('','', 10));
$pdf->SetHeaderData('' ,'1', 'Global Dominion Financing Inc.                                NON-PDC: SCHEDULE OF PAYMENTS - Borrower', PDF_HEADER_STRING);

// set header and footer fonts
// $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set some language-dependent strings (optional)
// if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
//     require_once(dirname(__FILE__).'/lang/eng.php');
//     $pdf->setLanguageArray($l);
// }

// ---------------------------------------------------------

// set default font subsetting mode
$pdf->setFontSubsetting(true);

// Set font
$pdf->SetFont('helvetica', '', 14, '', true);

// Add a page
// This method has several options, check the source code documentation for more information.
$pdf->AddPage('P', 'A4');

// Set some content to print
$html = <<<EOD
<p style="font-size:15px"><b>Product&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Personal Loan SB 2 (NO PDC) &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Released Date &nbsp;&nbsp;&nbsp;&nbsp;Jan 08, 2019</b></p>
<p style="font-size:15px; margin-top: 30px;"><b>PN Number&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; GD1002017000077
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<div>Payment Center</div>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
Reference No. &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b style="color:#6a95eb">710020170000772</b>
</b><p>
<p style="font-size:15px"><b>Borrower Name&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Cabrales, Sandrex Tusay</b></p>
<p style="font-size:15px;"><b>Borrower Address&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Brgy San Antonio Emerald Ave Jolibee Plaza Ortigas Pasig City</b></p>





EOD;

$html2head = <<<EOD
<table border="1" cellpadding="0" cellspacing="0" align="center" style="font-size:12px;">

 <tr nobr="true">
  <td>No.</td>
  <td>Check No</td>
  <td>Bank</td>
  <td>Account No</td>
  <td>Maturity</td>
  <td>Amount</td>
  <td>Status</td>
 </tr>

EOD;

$dd =0;
$html2 = "";

for($i = 0; $i < 5; $i++){
    $html2.=<<<EOD
    <tr nobr='true'>
    <td style='width 10px;'>$i</td>
    <td>ROW $i</td>
    <td>WITHOUT PDC</td>
    <td></td>
    <td>01/07/2018</td>
    <td>8,550.00</td>
    <td>Uncleared</td>
    </tr>
EOD;
}

$html2foot = <<<EOD
</table>
<p style="font-size: 14px;"><b>Notice</b></p>
<p style="font-size: 12px;">&nbsp;&nbsp;&nbsp;&nbsp;Please be informed that pursuant to tge credit Information System Act(R.A. No. 9510) 
(CISA) and its Implementing Rules and Regulations (IRR), creating the Credit Information Corporation (CIC) 
we are mandated to submit your basic credit data as defined under the CISA, as well as any regular updates or corrections to the CIC 
for consolidation and disclosure as may be authorized by CIC.
Consequently, your basic credit data may be shared with other lenders authorized by CIC, and other credit reporting agencies duly accredited by the CIC, for the purpose of establishing your credit worthiness.
</p>
<p style="font-size:12px">To ge more information, please visit www.creditinfo.gov.ph</p>
<p style="font-size:12px">1. Always Demand a reciept as proof of payment, Any claims by the borrower in the future that they have made payments directly to an GDFI employee or its authorized
collector/s will not be HONORED by Global Dominion Financing Inc. (GDFI) without any valid GDFI receipt/s.</p>
<p style="font-size:12px">2. In case of check payment, check issued must be payable to Global Dominion Financing Inc. *Pay to Cash check/s is not acceptable.</p>
<p style="font-size:12px">3. For Bounched check payments and those with no issued PDC's accounts, you can pay diretly thru our payment centers partners:</p>

<p style="font-size:12px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;a.Palawan Express Pera Padala
<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;b.Cebuana Lhuilier
<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;c.ML Kwarta Padala
<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;d.Unionbank Bills Payment
<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;e.I Remit
<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;f.EC Pay</p>

<p style="font-size:12px;">Please be informed that our accreduted payment centers are these just to receive payments and are not equipped to issue our company's official reciept.</p>
<p style="font-size:12px">&nbsp;&nbsp;&nbsp;This acknowledgement copy must be retained by the borrower for future references.</p>
<p style="font-size:12px">&nbsp;&nbsp;&nbsp;You may contact the following numbers from Monday to Friday, 9:00 AM to 6:00 PM</p>
<p style="font-size:12px">&nbsp;&nbsp;&nbsp;For payment concerns, request for ORCR, certification of full payment and other concers:</p>
<p style="font-size:12px">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Customer Success Department- Tel. No.: 631-4774/621-5538/0917-8272742, 0922-8147566 and 0925-8040007.</p>
<p style="font-size:12px">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Email address: accountcare@gdfi.ph<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;payment@gdfi.ph
</p>
<p style="font-size:12px;">
Accredited Collections Companies:
</p>
<p style="font-size:12px;">
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
Annapolis Credit Management Services, Inc (North Luzon Branches)

</p>
<p style="font-size:12px;">
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
Firts Maharlika Collection Management, Inc. (South Luzon Branches)
</p>

<p style="font-size:12px;">
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
Sparta Credit Management Services, Inc. (VISMIN Area)
</p>

<p style="font-size:12px;">Your concern is our priority.</p>
<p style="font-size:12px">Disclaimer:</p>
<p style="font-size:12px;">All payments must be payable to Global Dominion Financing, Inc. only.</p>
<hr />
<br />
<p></p>
<p style="font-size:12px;">Total Check Amount&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Php 23,000.89</b></p>
<p></p>
<p style="font-size:11px;">Prepared By: _____________________ Released By: _______________________ Approved by: ___________________</p>
<p></p>
<p style="font-size:11px;">_________________________</p>
<p style="font-size:11px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Borrower's Signature</p>
EOD;

// Print text using writeHTMLCell()
$pdf->writeHTMLCell(0, 0, '', 32, $html, 0, 1, 0, true, '', true);
$pdf->writeHTMLCell(0, 0, '', 95, $html2head .$html2. $html2foot, 0, 1, 0, true, '', true);

// ---------------------------------------------------------

// Close and output PDF document
// This method has several options, check the source code documentation for more information.
$pdf->Output('Sofia.pdf', 'I');

//============================================================+
// END OF FILE
//============================================================+
}


// print_r(json_encode($result));




?>