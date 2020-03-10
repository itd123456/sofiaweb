<?php

class sofia{

    private $user = 'saprog';

    private $pass = 'SQL@2012!';

    private $db = 'GLOBAL_SOFIADB';

    private $host = '192.168.0.26';

    private $conn;


    public function __construct(){

        $this->conn = new PDO("sqlsrv:server=".$this->host.";Database=".$this->db, $this->user, $this->pass);

    }


    public function payment($data){


        $pn = $data['pnpay'];
        $sql = "select top 15 a.MPDC_SEQUENCE, a.MPDC_MATURITY, a.MPDC_AMOUNT, a.MPDC_STATUS, 
        b.LOAN_PN_NUMBER, b.LOAN_RELEASED_DATE,
        c.BORR_ADDRESS, c.BORR_FIRST_NAME, c.BORR_MIDDLE_NAME, c.BORR_LAST_NAME, c.BORR_SUFFIX,
        d.BRAN_NAME, d.BRAN_ADDRESS,
        e.PROD_NAME 
        from FI_AMORTSCHED_MASTER a
        left join LM_LOAN b on 
        a.MPDC_PN_NUMBER = b.LOAN_PN_NUMBER 
        left join PR_BORROWERS c on
        b.LOAN_BORROWER_CODE = c.BORR_CODE
        left join PR_BRANCH d on
        b.LOAN_BR = d.BRAN_CODE
        left join PR_PRODUCT e on
        b.LOAN_PRODUCT_CODE = e.PROD_CODE where a.MPDC_PN_NUMBER = '$pn'";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(array());
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $return[][] = $data;



    }


}


?>