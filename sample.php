USE [webloan_sofia_integration]
GO
/****** Object:  StoredProcedure [dbo].[leads_push_to_sofia]    Script Date: 1/9/2020 2:36:18 PM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
ALTER procedure [dbo].[leads_push_to_sofia]
@leads_id_code bigint=10052
as
/***
  Design to push the leads data to sofia

  Last Update: April 19, 2018 - fix renewal
***/
set nocount on

declare @bk varchar(3),
		@bch varchar(3),
		@loan_no varchar(20),
		@company varchar(10),
		@bch_add varchar(100),
		
		@cis_no varchar(10),
		@lname varchar(50),
		@fname varchar(50),
		@mname varchar(50),
		@p_bday datetime,
		@appelation varchar(3),

		@nowdate datetime,
		@APPL_DATE_APPLIED datetime,
		@APPL_AMOUNT_APPLIED decimal(18,2), ---numeric to decimal (for test ko po 03/22/2018)

		@ADDRESS varchar(4000),
		@email varchar(50),
		@MOBILENO varchar(20),
		@telNO varchar(20),
		@h_stayed_since datetime,
		@h_stayed_since_years int,

		@run_cis_no varchar(10),
		@run_address varchar(4000),
		@run_contact_no varchar(50),
		@run_contact_no2 varchar(50),
		@run_name varchar(130),
		@run_count int,

		@ao varchar(20),
        @sales_user varchar(20),
		@crecom varchar(20),
		@crd varchar(20),

		@interest_rate decimal(10,8), ---numeric to decimal (for test ko po 03/12/2018)
		@term_in_months tinyint, ---int to tinyint (for test ko po 03/22/2018)
		@crecom_approval decimal(18,2), ---numeric to decimal (for test ko po 03/22/2018)

		@p_gender tinyint,
		@dynamic_address varchar(100),
		@APPL_GRACE_PERIOD tinyint,
		@loan_product_final varchar(10),
		@prev_loan_no varchar(20),
		@prev_loan_balance decimal(18,2), ---numeric to decimal (for test ko po 03/22/2018)

		@source_of_fund_path varchar(100),
		@BORR_MAIDEN_NAME varchar(100),

		@WBOR_MOBILE_NUMBER varchar(27),
		@WBOR_TEL_NUMBER varchar(20),
		@WBOR_COMPANY_NAME varchar(150),
		@WBOR_ADDRESS1 varchar(150),

		@civil_status tinyint,

		@operation_type tinyint, /*** 0-insert, 1-update ***/
		@APPL_NEWCODE varchar(19),
		@hcnt int,
		@GEN_CODE VARCHAR(19)

--- initialization ---
set @company='GDFI'
set @nowdate=getdate()
set @operation_type=0 /** default to insert **/

select top 1 @bk=bk
  from webloan.dbo.bank_set
    with (nolock)

select @bch=ldx.preferredbch,
	   @loan_no=ldx.loan_no,
	   @cis_no=ldx.cis_no,
	   @APPL_DATE_APPLIED=ldx.tdate,
	   @APPL_AMOUNT_APPLIED=ldx.amount_requested,

	   @lname=c.lname,
	   @fname=c.fname,
	   @mname=c.mname,
	   @p_bday=c.p_bday,
	   @p_gender=c.p_gender,
	   @civil_status=c.civil_status,
	   @appelation=c.appelation,

	   @source_of_fund_path=c.source_of_fund_path,

	   @ADDRESS=ltrim(rtrim(
					ltrim(rtrim(isnull(c.h_sadd,''))) + ' ' +  
					ltrim(rtrim(isnull(c.h_village,''))) + ' ' +  
					ltrim(rtrim(isnull(webloan.dbo.get_dynamic_address(c.dynamic_address),'')))
					 )),
	   @dynamic_address=c.dynamic_address,
	   @email=ltrim(rtrim(isnull(c.email,''))),
	   @MOBILENO=dbo.format_cell(c.h_mobile),
	   @telNO=ltrim(rtrim(isnull(c.h_phone,''))),
	   @h_stayed_since=isnull(c.h_stayed_since,@nowdate),
	   @BORR_MAIDEN_NAME=ltrim(rtrim(isnull(c.mothers_maiden_name,''))),

	   @WBOR_MOBILE_NUMBER=dbo.format_cell(c.b_mobile),
	   @WBOR_TEL_NUMBER=ltrim(rtrim(isnull('000' + c.b_phone,''))), --edited 4/5/2018
	   @WBOR_COMPANY_NAME=ltrim(rtrim(isnull(c.b_comp,''))),
	   @WBOR_ADDRESS1=ltrim(rtrim(isnull(c.b_barangay,''))),
	   
	   @ao=ltrim(rtrim(isnull(ao,''))),
       @sales_user=ltrim(rtrim(isnull(sales_user,''))),
	   @crecom=CASE WHEN ltrim(rtrim(isnull(crecom,''))) = '' THEN ltrim(rtrim(isnull(orig_crecom,''))) ELSE ltrim(rtrim(isnull(crecom,''))) END, --edited 3/11/2019
	   @crd=ltrim(rtrim(isnull(crd,'')))

	   --- Application Data ---
	   ,@interest_rate=ldx.interest_rate
	   ,@term_in_months=ldx.term_in_months
	   ,@crecom_approval=ldx.crecom_approval
	   ,@APPL_GRACE_PERIOD=ldx.grace_period

	   ,@loan_product_final=ldx.loan_product_final
	   ,@prev_loan_no=ldx.prev_loan_no
	   ,@prev_loan_balance=ldx.prev_loan_balance
	   ,@APPL_NEWCODE=ltrim(rtrim(isnull(APPL_NEWCODE,''))) /** this will determine the application code **/
	   ,@GEN_CODE=ltrim(rtrim(isnull(sofia_gen_code,'')))
  from webloan.dbo.leads_data ldx
    with (nolock)
  join webloan.dbo.cis_info c
    with (nolock)
	on ldx.cis_no=c.cis_no
  where ldx.id_code=@leads_id_code

--- sofia is limited to 40 chararcters ---
set @lname=ltrim(rtrim(left(@lname,40)))

--- get branch fullname ---
select @bch_add=bch_add
  from webloan.dbo.branch_set
    with (nolock)
  where bch=@bch

---compute for the next anniversarry ---
set @h_stayed_since_years=datediff(year,@h_stayed_since,@nowdate)



declare @BORR_AREA varchar(13)
set @BORR_AREA=dbo.get_zone_area(@dynamic_address)
if len(@BORR_AREA)=0
begin
  print 'Zone Area not found.'
  return 0
end

if len(@GEN_CODE)=0 /** no borrower code yet **/
begin
  set @GEN_CODE=dbo.is_borrower_exists(@lname,@fname,@p_bday)
end

  declare @BORR_GENDER bit,
		  @BORR_SALUTATION varchar(13),
		  @BORR_LENGTH_STAY int

  set @BORR_LENGTH_STAY=datediff(year,@h_stayed_since,@nowdate)
  if @BORR_LENGTH_STAY < 0
  begin
    set @BORR_LENGTH_STAY=0
  end

  	IF @p_gender=1 -- male --
	begin
		SET @BORR_GENDER = 1
		SET @BORR_SALUTATION  = '0101000000001'
	end
	ELSE 
	begin
		SET @BORR_GENDER = 0
		SET @BORR_SALUTATION  = '0101000000002'
	end

	set @civil_status=dbo.civil_status2sofia(@civil_status)

if len(@GEN_CODE)=0
begin
  set @operation_type=0 /** insert **/
end
else
begin
  set @operation_type=1 /** Update **/

  	--- save the borrower code ---
	update webloan.dbo.leads_data
	  set sofia_gen_code=@GEN_CODE
	  where id_code=@leads_id_code
end


if @operation_type=0 /** insert **/
begin
  exec borrower_insert  @bch,
						@BORR_SALUTATION,
						@lname, --@BORR_LAST_NAME varchar(40)='',
						@fname, --@BORR_FIRST_NAME varchar(50)='',
						@mname, --@BORR_MIDDLE_NAME varchar(40)='',
						@appelation, --@BORR_SUFFIX varchar(20)='',
						@p_bday, --@BORR_BIRTH_DATE datetime = null ,
						@BORR_MAIDEN_NAME,
						@civil_status,
						@BORR_GENDER, 
						@ADDRESS,--	@BORR_ADDRESS varchar(MAX) = null ,
						@BORR_AREA,
						@BORR_LENGTH_STAY,--	@BORR_LENGTH_STAY tinyint = null ,
						@telNO,--	@BORR_TELNO varchar(20) = null ,
						@MOBILENO,--	@BORR_MOBILENO varchar(27) = null ,
						@email,--	@BORR_EMAIL varchar(50) = null ,
						'',--	@BORR_REMARKS ntext = null ,
						1, --	@BORR_STATUS tinyint = null ,
						0x, --	@BORR_PICTURE image = null ,

						'0101000001713',--	@BORR_NATIONALITY varchar(13) = null ,
						'',--	@BORR_COUNTRY varchar(13) = null ,
						'',--	@BORR_EDUCATION varchar(13) = null ,
						'',--	@BORR_COURSE varchar(13) = null ,
						'',--	@BORR_LAST_SCHOOL varchar(100) = null ,
						0,--	@BORR_YEAR_GRADUATED int = null ,

						0,--	@BORR_SMS_SOA bit = null ,
						0,--	@BORR_SMS_PAYMENT bit = null ,
						'',--	@BORR_ACCESS_CODE varchar(50) = null ,

						@GEN_CODE OUTPUT

	--- save the borrower code ---
	update webloan.dbo.leads_data
	  set sofia_gen_code=@GEN_CODE
	  where id_code=@leads_id_code

end
else
begin
	exec borrower_update    @GEN_CODE,
							@BORR_SALUTATION,
							@lname, --@BORR_LAST_NAME varchar(40)='',
							@fname, --@BORR_FIRST_NAME varchar(50)='',
							@mname, --@BORR_MIDDLE_NAME varchar(40)='',
							@appelation, --@BORR_SUFFIX varchar(20)='',
							@p_bday, --@BORR_BIRTH_DATE datetime = null ,
							@BORR_MAIDEN_NAME,
							@civil_status,
							@BORR_GENDER, 
							@ADDRESS,--	@BORR_ADDRESS varchar(MAX) = null ,
							@BORR_AREA,
							@BORR_LENGTH_STAY,--	@BORR_LENGTH_STAY tinyint = null ,
							@telNO,--	@BORR_TELNO varchar(20) = null ,
							@MOBILENO,--	@BORR_MOBILENO varchar(27) = null ,
							@email,--	@BORR_EMAIL varchar(50) = null ,
							'',--	@BORR_REMARKS ntext = null ,
							1, --	@BORR_STATUS tinyint = null ,
							0x, --	@BORR_PICTURE image = null ,

							'0101000001713',--	@BORR_NATIONALITY varchar(13) = null ,
							'',--	@BORR_COUNTRY varchar(13) = null ,
							'',--	@BORR_EDUCATION varchar(13) = null ,
							'',--	@BORR_COURSE varchar(13) = null ,
							'',--	@BORR_LAST_SCHOOL varchar(100) = null ,
							0,--	@BORR_YEAR_GRADUATED int = null ,

							0,--	@BORR_SMS_SOA bit = null ,
							0,--	@BORR_SMS_PAYMENT bit = null ,
							''--	@BORR_ACCESS_CODE varchar(50) = null ,
end

declare @cis_data table(
                        cis_no varchar(10),
						relation tinyint not null default 0
						primary key (cis_no)
						)

declare @run_relation tinyint,
        @relation tinyint,
		@age int,

		@WBOR_LICENSE_NUMBER varchar(20),
		@WBOR_SSS_NUMBER varchar(20),
		@WBOR_TAX_NUMBER varchar(20),
		@WBOR_DRIVER_LICENSE varchar(20),

		@WBOR_MONTHLY_INCOME decimal,
		@monthly_income_str varchar(100),
		@WBOR_EMPLOYMENT varchar(100),

		@r_lname varchar(50),
		@r_fname varchar(50),
		@r_mname varchar(50),
		@r_p_bday datetime,
		@r_appelation varchar(3),
		@r_p_gender tinyint,
		@r_MOBILENO varchar(20),
		@r_telNO varchar(20),
		@r_ADDRESS varchar(4000),
		@r_BORR_MAIDEN_NAME varchar(100),
		@r_civil_status tinyint


set @WBOR_EMPLOYMENT=webloan.dbo.mis_get_path_description(97,@source_of_fund_path)
set @WBOR_EMPLOYMENT=ltrim(rtrim(isnull(@WBOR_EMPLOYMENT,'')))

set @WBOR_EMPLOYMENT=case
						when @WBOR_EMPLOYMENT='Dentist' then 'Self-Employed'
						when @WBOR_EMPLOYMENT='Doctor' then 'Self-Employed'
						when @WBOR_EMPLOYMENT='Employed' then 'Private'
						when @WBOR_EMPLOYMENT='Medical Doctor' then 'Self-Employed'
						when @WBOR_EMPLOYMENT='N/A' then 'Self-Employed'
						when @WBOR_EMPLOYMENT='Optometrist' then 'Self-Employed'
						when @WBOR_EMPLOYMENT='Pensioner' then 'Self-Employed'
						when @WBOR_EMPLOYMENT='Allottee / Beneficiary' then 'Self-Employed'
						when @WBOR_EMPLOYMENT='Self-Employed' then 'Self-Employed'
						when @WBOR_EMPLOYMENT='Veterinarian' then 'Self-Employed'
						else 'Self-Employed'
					 end


set @WBOR_MONTHLY_INCOME=0
set @monthly_income_str=webloan.dbo.check_list_get_item(@cis_no,'I_SAL')
set @monthly_income_str=ltrim(rtrim(isnull(@monthly_income_str,'0')))
if isnumeric(@monthly_income_str)=1
begin
  set @WBOR_MONTHLY_INCOME=cast(@monthly_income_str as numeric(18,2))
  if @WBOR_MONTHLY_INCOME < 0
  begin
    set @WBOR_MONTHLY_INCOME=0
  end
end

set @WBOR_LICENSE_NUMBER=webloan.dbo.check_list_get_item(@cis_no,'ID3')
set @WBOR_SSS_NUMBER=webloan.dbo.check_list_get_item(@cis_no,'ID11')
set @WBOR_TAX_NUMBER=webloan.dbo.check_list_get_item(@cis_no,'ID8')
set @WBOR_DRIVER_LICENSE=webloan.dbo.check_list_get_item(@cis_no,'ID2')

set @WBOR_LICENSE_NUMBER=ltrim(rtrim(isnull(@WBOR_LICENSE_NUMBER,'')))
set @WBOR_SSS_NUMBER=ltrim(rtrim(isnull(@WBOR_SSS_NUMBER,'')))
set @WBOR_TAX_NUMBER=ltrim(rtrim(isnull(@WBOR_TAX_NUMBER,'')))
set @WBOR_DRIVER_LICENSE=ltrim(rtrim(isnull(@WBOR_DRIVER_LICENSE,'')))

--- Work Related ---
exec work_insert @bch,@GEN_CODE
				,@WBOR_EMPLOYMENT
				,@WBOR_COMPANY_NAME
				,'' -- @WBOR_DTI_SEC varchar(150) = null ,
				,'' -- @WBOR_BUSINESS_TYPE varchar(13) = null ,
				,@WBOR_ADDRESS1
				,'' -- @WBOR_ADDRESS2 varchar(150) = null ,
				,'' -- @WBOR_CITY varchar(13) = null ,
				,'' -- @WBOR_STATE varchar(13) = null ,
				,0 -- @WBOR_NUMBER_YEARS tinyint = null ,
				,@WBOR_MOBILE_NUMBER
				,@WBOR_TEL_NUMBER
				,'' -- @WBOR_POSITION varchar(13) = null ,
				,@WBOR_MONTHLY_INCOME -- @WBOR_MONTHLY_INCOME decimal = null ,
				,'' -- @WBOR_EMPLOY_STATUS varchar(50) = null ,
				,@WBOR_LICENSE_NUMBER
				,@WBOR_SSS_NUMBER
				,@WBOR_TAX_NUMBER
				,@WBOR_DRIVER_LICENSE
				,null --@WBOR_DRIVER_LICENSE_EXP datetime = null

--- Spouse ------------------------------------------------------------------------
delete @cis_data /** clear it first **/

insert @cis_data(cis_no)
  select top 10 r_cis_no
    from webloan.dbo.cis_relation
	  with (nolock)
	where cis_no=@cis_no and
		  relation=2 /** Spouse **/
if @@ROWCOUNT > 0
begin
    -- delete all reference by borrower code --
    exec spouse_delete_by_borrower @GEN_CODE

	declare c_ref cursor FAST_FORWARD
		for select cis_no
			from @cis_data

	open c_ref

	fetch next from c_ref
		into @run_cis_no

	while (@@fetch_status=0)
	begin
	   select @r_lname=c.lname,
			  @r_fname=c.fname,
			  @r_mname=c.mname,
			  @r_p_bday=c.p_bday,
			  @r_p_gender=c.p_gender
		 from webloan.dbo.cis_info c
		   with (nolock)
		 where c.cis_no=@run_cis_no

		 set @r_lname=ltrim(rtrim(left(@r_lname,40)))

		set @BORR_GENDER=case
		                   -- male --
		                   when @p_gender=1 then 0
						   else 1
						 end --edited 4/5/2018 0 = male, 1 = female
		
	  --- insert reference ---
	  exec spouse_insert    @bch,
							@GEN_CODE,
							--------------------------------------
							@r_lname, ---@SBOR_LAST_NAME varchar(40) = null ,
							@r_fname, ---@SBOR_FIRST_NAME varchar(50) = null ,
							@r_mname, ---@SBOR_MIDDLE_NAME varchar(40) = null ,
							@BORR_GENDER, ---@SBOR_GENDER bit = null ,
							@r_p_bday, ---@SBOR_BIRTH_DATE datetime = null , ---edited 4/5/2018
							'', --@SBOR_EDUCATION varchar(13) = null ,
							'', --@SBOR_COURSE varchar(13) = null ,
							'', --@SBOR_LAST_SCHOOL varchar(100) = null ,
							0 --@SBOR_YEAR_GRADUATED INT

	   /*** fetch next record ***/   
	   fetch next from c_ref
		into @run_cis_no
	end

	/*** closing of cursor ****/
	close c_ref
	deallocate c_ref
end
--- Spouse ------------------------------------------------------------------------


--- Family ------------------------------------------------------------------------
delete @cis_data /** clear it first **/

insert @cis_data(cis_no,relation)
  select r_cis_no,relation
    from webloan.dbo.cis_relation
	  with (nolock)
	where cis_no=@cis_no and
		  relation in (0,1,5) /*** 0-Parent 1-Child, 5-siblings **/
if @@ROWCOUNT > 0
begin
    -- delete all reference by borrower code --
    exec family_delete_by_borrower @GEN_CODE

	declare c_ref cursor FAST_FORWARD
		for select cis_no,relation
			from @cis_data

	open c_ref

	fetch next from c_ref
		into @run_cis_no,@run_relation

	while (@@fetch_status=0)
	begin

	   select @r_lname=c.lname,
			  @r_fname=c.fname,
			  @r_mname=c.mname,
			  @r_p_bday=c.p_bday,
			  @r_p_gender=c.p_gender,
			  @r_appelation=c.appelation,
			  @r_ADDRESS=case
			               when @run_relation=1 then webloan.dbo.check_list_get_item(@run_cis_no,'EDUC1')
						   else 
								ltrim(rtrim(
								ltrim(rtrim(isnull(c.h_sadd,''))) + ' ' +  
								ltrim(rtrim(isnull(c.h_village,''))) + ' ' +  
								ltrim(rtrim(isnull(webloan.dbo.get_dynamic_address(c.dynamic_address),'')))
								 ))
						 end,
			   @r_MOBILENO=dbo.format_cell(c.h_mobile),
			   @r_telNO=ltrim(rtrim(isnull(c.h_phone,'')))

		 from webloan.dbo.cis_info c
		   with (nolock)
		 where c.cis_no=@run_cis_no

		 set @r_lname=ltrim(rtrim(left(@r_lname,40)))

		set @BORR_GENDER=case
		                   -- male --
		                   when @r_p_gender=1 then 1
						   else 0
						 end

		SET @BORR_SALUTATION=case
						-- male --
						when @r_p_gender=1 then '0101000000001'
						else '0101000000002'
						end

		set @relation=case
		                 --- parent ---
						 when @run_relation=0 and @r_p_gender=1 then 0
					     when @run_relation=0 and @r_p_gender=2 then 1

						 --- child ---
						 when @run_relation=1 and @r_p_gender=1 then 4
					     when @run_relation=1 and @r_p_gender=2 then 5

						 --- sibling ---
						 when @run_relation=5 then 3

						 else 0
					   end

		set @age=datediff(month,@r_p_bday,@nowdate) / 12

	  --- insert reference ---
		exec family_insert  @GEN_CODE,
							@relation,   /*** 0 Father, 1 Mother, 3 Siblingss, 4, Son, 5 Daugther ***/

							@BORR_SALUTATION,
							@r_lname,
							@r_fname,
							@r_mname,
							@r_appelation,
							@r_telNO,
							@r_MOBILENO,
							@r_ADDRESS,
							'', --@FBOR_ALT_ADDRESS varchar(max) = null,
							@age

	   /*** fetch next record ***/   
	   fetch next from c_ref
		into @run_cis_no,@run_relation
	end

	/*** closing of cursor ****/
	close c_ref
	deallocate c_ref
end
--- Family ------------------------------------------------------------------------


--- character reference ------------------------------------------------------------------------
delete @cis_data /** clear it first **/

insert @cis_data(cis_no)
  select top 10 r_cis_no
    from webloan.dbo.cis_relation
	  with (nolock)
	where cis_no=@cis_no and
		  relation=28 /** character **/
if @@ROWCOUNT > 0
begin
    -- delete all reference by borrower code --
    exec reference_delete_by_borrower @GEN_CODE

	declare c_ref cursor FAST_FORWARD
		for select cis_no
			from @cis_data

	open c_ref

	fetch next from c_ref
		into @run_cis_no

	while (@@fetch_status=0)
	begin
	   select @run_address=ltrim(rtrim(
					ltrim(rtrim(isnull(c.h_sadd,''))) + ' ' +  
					ltrim(rtrim(isnull(c.h_village,''))) + ' ' +  
					ltrim(rtrim(isnull(webloan.dbo.get_dynamic_address(c.dynamic_address),'')))
					 )),
			  @run_contact_no2=dbo.format_cell(h_mobile),
			  @run_contact_no=ltrim(rtrim(isnull(h_phone,''))),
			  
		      @run_name=webloan.dbo.get_name_from_cis(cis_no)
		 from webloan.dbo.cis_info c
		   with (nolock)
		 where c.cis_no=@run_cis_no

	  --- insert reference ---
	   exec reference_insert @bch,
							 @GEN_CODE,
							 @run_name,
							 @run_address,
							 @run_contact_no,
							 @run_contact_no2

	   /*** fetch next record ***/   
	   fetch next from c_ref
		into @run_cis_no
	end

	/*** closing of cursor ****/
	close c_ref
	deallocate c_ref
end
--- character reference ------------------------------------------------------------------------


declare @APPL_PRODUCT_CODE varchar(20)

---get converted product code ---
set @APPL_PRODUCT_CODE=dbo.product_webloan2sofia(@loan_product_final)

declare @APPL_AO_CODE varchar(13),
		@APPL_CREMAN_CODE varchar(13),
		@APPL_CRD_CODE varchar(13)

declare @run_agent_code varchar(19)
	declare @agents table(
            AGENTS_CODE varchar(19),
			created datetime
			primary key(AGENTS_CODE)
			)

	insert @agents(AGENTS_CODE,created)
		select distinct ltrim(rtrim(isnull(dbo.get_user_code_from_cis_no(r_cis_no),''))),created
			from webloan.dbo.cis_relation
			with (nolock)
			where cis_no=@cis_no and
				relation in (20,24)
--- get one ---
select top 1 @run_agent_code=AGENTS_CODE
  from @agents
  order by created

set @run_agent_code=ltrim(rtrim(isnull(@run_agent_code,'')))
				

set @APPL_AO_CODE=dbo.get_user_code(@ao)
set @APPL_CREMAN_CODE=dbo.get_user_code(@crecom)
set @APPL_CRD_CODE=dbo.get_user_code(@crd)

----for test loan type 4/11/2018--
declare @loan_type_str varchar(100), --for test loan type 4/11/2018--
		@loan_type tinyint

set @loan_type_str=webloan.dbo.check_list_get_item(@cis_no,'LA01')
/** 1-NEW 2-Renewal  **/
set @loan_type=case
				  when @loan_type_str='TYPE01' then 1
				  when @loan_type_str='TYPE02' then 2
				  when @loan_type_str='TYPE03' then 2
				  else 1
			   end
--- Application Insert ---
declare @PN_NUMBER varchar(20)
set @PN_NUMBER=''

if len(@APPL_NEWCODE)=0 or
   (len(@APPL_NEWCODE) > 0 and dbo.is_application_cancelled(@APPL_NEWCODE)=1)
begin    
	exec application_insert     @bch,
								'CRM',
								@GEN_CODE, --@APPL_BORROWER_CODE varchar(19) = null ,
								@BORR_SALUTATION, --@APPL_SALUTATION varchar(13) = null ,
								@lname, --@APPL_LAST_NAME varchar(40) = null ,
								@fname, --@APPL_FIRST_NAME varchar(50) = null ,
								@mname, --@APPL_MIDDLE_NAME varchar(40) = null ,
								@appelation, --@APPL_SUFFIX varchar(20) = null ,
								null, --@APPL_OTHER_PAYTO varchar(130) = null ,
								@p_bday, --@APPL_BIRTH_DATE datetime = null ,

								@loan_type, /** 1-NEW 2-Renewal  **/

								@APPL_AMOUNT_APPLIED, --@APPL_AMOUNT_APPLIED decimal(18,2) = null ,
								@term_in_months, --@APPL_TERMS_APPLIED tinyint = null ,
								null, --@APPL_INTERESTRATE_APPLIED decimal(9,5) = null ,
								@APPL_DATE_APPLIED,
								@APPL_PRODUCT_CODE, --- translated ---
								@ADDRESS,--	@BORR_ADDRESS varchar(MAX) = null ,
								@BORR_AREA,
								@BORR_LENGTH_STAY, --@APPL_LENGTH_STAY tinyint = null ,
								@telNO, --@APPL_TELNO varchar(20) = null ,
							    @MOBILENO, --@APPL_MOBILENO varchar(27) = null ,
							    @email, --@APPL_EMAIL varchar(50) = null ,
								'', --@run_agent_code, --@APPL_AGENTS_CODE,
								null, --@APPL_COBORROWERS_CODE varchar(19) = null ,
								null, --@APPL_COMAKERS_CODE varchar(19) = null ,
								null, --@APPL_MISC_CODE varchar(13) = null ,
								@APPL_AO_CODE,
								@APPL_CREMAN_CODE,
								@APPL_CRD_CODE,
								null, --@APPL_CRD_DATEIN datetime = null ,
								null, --@APPL_CRD_DATEOUT datetime = null ,
								@crecom_approval, --@APPL_AMOUNT_APPROVED decimal(18,2) = null ,
								@term_in_months, --@APPL_TERMS_APPROVED tinyint = null ,
								@interest_rate, --@APPL_INTERESTRATE_APPROVED decimal(9,5) = null ,
								@crecom_approval, --@APPL_AMOUNT_ACQUIRED decimal(18,2) = null ,
								@term_in_months, --@APPL_TERMS_ACQUIRED tinyint = null ,
								@interest_rate, --@APPL_INTERESTRATE_ACQUIRED decimal(9,5) = null ,
								@APPL_GRACE_PERIOD, --@APPL_GRACE_PERIOD tinyint = null ,
								0, --@APPL_ADDON decimal(18,2) = null ,
								0, --@APPL_INCLUDE_ADDON bit = null ,
								2, -- @APPL_PAYMENT_MODE tinyint = null , /*** 1-semi monthly 2, monthly **/
								@APPL_DATE_APPLIED, --@APPL_NEEDED_DATE datetime = null ,
				'', --@APPL_AGENCY varchar(13) = null ,
				'', --@APPL_COUNTRY_DESTI varchar(13) = null ,
								0, --@APPL_CAR_YEAR_MODEL INT = null ,
								@prev_loan_no, --@APPL_PREV_PN_NUMBER--
								@prev_loan_balance, --@APPL_PREV_BALANCE--
								0, --@APPL_PREV_REBATE decimal(18,2) = null ,
								2, --@APPL_STATUS tinyint = null ,
								0, --@APPL_SOURCE tinyint = null ,
								null, --@APPL_INSTRUCTIONS ntext = null ,
								@nowdate, --@APPL_CREATED_DATE datetime = null ,
				0, --@APPL_RENEWAL tinyint = null,
								0, --@APPL_ADDT_INTEREST decimal(18,2)= null,
								0, --@APPL_ADDT_INTEREST_DAYS bit = null,
								null, --@APPL_REMARKS ntext   = null ,
								null, --@APPL_RESERVED_1 int = null ,
								null, --@APPL_RESERVED_2 decimal(18,2)= null ,
								null, --@APPL_RESERVED_3 datetime= null ,
								null, --@APPL_DATA       ntext = null ,
								null, --@APPL_FINANCE_CHARGES decimal(18,2)= null,
								null, --@APPL_EFFECTIVE_RATE decimal(18,2)= null,
								null, --@APPL_PN_NUMBER varchar(20) = null,
								null, --@APPL_OBALANCE decimal = null ,
								null, --@APPL_OTERMS tinyint = null ,
								null, --@APPL_ODAYS tinyint = null ,
								null, --@APPL_LASTPAYMENT_DATE datetime = null ,
								null, --@APPL_PREV_LOANSTATUS	tinyint = null,
								null, --@APPL_GLJV_CODE varchar(20) = null,
								null, --@APPL_TOTAL_ADDTINTEREST  decimal (18,2) = null,
								@APPL_NEWCODE output

				/** save the application code **/
				update webloan.dbo.leads_data
				  set APPL_NEWCODE=@APPL_NEWCODE,
					  saved_to_sofia=@nowdate /** saved the date and time of pushed to sofia **/
				  where id_code=@leads_id_code

			set @hcnt=0
			declare c_agents cursor FAST_FORWARD
				for select AGENTS_CODE
						from @agents

			open c_agents

			fetch next from c_agents
				into @run_agent_code

			while (@@fetch_status=0)
			begin

			    set @hcnt=@hcnt + 1

				exec LM_APPLICATION_AGENTS_INSERT   @APPL_NEWCODE,
													'', --@AAGT_APP_AGENTS varchar(19)
													@run_agent_code, --,@AAGT_AGENT_CODE varchar(13)
													@hcnt, --,@AAGT_HIERARCHY tinyint
													0.00, --,@AAGT_RATE decimal(9,5)
													0, --,@AAGT_AMOUNT decimal(18,2)
													0, --,@AAGT_VAT decimal(18,2)
													'', --,@AAGT_PN_NUMBER varchar(20) = null
													'', --,@AAGT_VP_CODE varchar(20) = null
													1, --,@AAGT_STATUS tinyint = null
													0 --,@AAGT_CHANGED tinyint = null

			   /*** fetch next record ***/   
			   fetch next from c_agents
				into @run_agent_code
			end

			/*** closing of cursor ****/
			close c_agents
			deallocate c_agents

end
else
begin
  set @PN_NUMBER=ltrim(rtrim(isnull(dbo.get_loan_no_from_sofia(@APPL_NEWCODE),'')))

  --exec application_update @APPL_NEWCODE,
		--				  @BORR_SALUTATION,
		--				  @lname,
		--				  @fname,
		--				  @mname,
		--				  @appelation,
		--				  @p_bday,
		--				  @loan_type,
		--				  @ADDRESS,
		--				  @BORR_AREA,
		--				  @BORR_LENGTH_STAY,
		--				  @telNO,
		--				  @MOBILENO,
		--				  @email
end


--- Co Borrower ------------------------------------------------------------------------
delete @cis_data /** clear it first **/

insert @cis_data(cis_no)
  select r_cis_no /** three records **/
    from webloan.dbo.cis_relation
	  with (nolock)
	where cis_no=@cis_no and
		  relation=17 /** Co Borrower **/

/** delete it first **/
exec co_borrower_comaker_delete @APPL_NEWCODE,1

--- reset counter ---
set @hcnt=0
if @@ROWCOUNT > 0
begin
    declare @run_LAST_NAME varchar(40),
			@run_FIRST_NAME varchar(50),
			@run_MIDDLE_NAME varchar(40),
			@run_SUFFIX varchar(20),
			@run_BIRTH_DATE datetime,
			@run_p_gender tinyint,
			@run_civil_status tinyint,
			@run_h_stayed_since datetime,
			@run_email varchar(50),
			@co_borrower_new varchar(19)

    set @run_count=0 --- initialize ---
	declare c_ref cursor FAST_FORWARD
		for select cis_no
			from @cis_data

	open c_ref

	fetch next from c_ref
		into @run_cis_no

	while (@@fetch_status=0)
	begin
	   set @run_count=@run_count + 1

	   select @run_address=ltrim(rtrim(
					ltrim(rtrim(isnull(c.h_sadd,''))) + ' ' +  
					ltrim(rtrim(isnull(c.h_village,''))) + ' ' +  
					ltrim(rtrim(isnull(webloan.dbo.get_dynamic_address(c.dynamic_address),'')))
					 )),
			  @r_MOBILENO=dbo.format_cell(c.h_mobile),
			  @r_telNO=ltrim(rtrim(isnull(c.h_phone,''))),
			  @BORR_AREA=dbo.get_zone_area(c.dynamic_address),
			  @run_LAST_NAME=ltrim(rtrim(isnull(left(c.lname,40),''))),
			  @run_FIRST_NAME=ltrim(rtrim(isnull(c.fname,''))),
			  @run_MIDDLE_NAME=ltrim(rtrim(isnull(left(c.mname,40),''))),
			  @run_SUFFIX=ltrim(rtrim(isnull(c.appelation,''))),
			  @run_BIRTH_DATE=c.p_bday,
			  @run_p_gender=c.p_gender,
			  @run_civil_status=c.civil_status,
			  @run_h_stayed_since=isnull(c.h_stayed_since,@nowdate),
			  @r_BORR_MAIDEN_NAME=ltrim(rtrim(isnull(c.mothers_maiden_name,''))),
			  @run_email=ltrim(rtrim(isnull(c.email,''))),
			  @p_gender=c.p_gender,
			  @civil_status=c.civil_status
		 from webloan.dbo.cis_info c
		   with (nolock)
		 where c.cis_no=@run_cis_no

		 --- check if exists ---
		 set @co_borrower_new=dbo.is_borrower_exists(@run_LAST_NAME,@run_FIRST_NAME,@run_BIRTH_DATE)
         if len(@co_borrower_new)=0
         begin
			set @BORR_LENGTH_STAY=datediff(year,@run_h_stayed_since,@nowdate)

  			IF @p_gender=1 -- male --
			begin
				SET @BORR_GENDER = 1
				SET @BORR_SALUTATION  = '0101000000001'
			end
			ELSE 
			begin
				SET @BORR_GENDER = 0
				SET @BORR_SALUTATION  = '0101000000002'
			end

		   --- translate civil status ---
		   set @civil_status=dbo.civil_status2sofia(@civil_status)

		   exec borrower_insert  @bch,
							@BORR_SALUTATION,
							@run_LAST_NAME, --@BORR_LAST_NAME varchar(40)='',
							@run_FIRST_NAME, --@BORR_FIRST_NAME varchar(50)='',
							@run_MIDDLE_NAME, --@BORR_MIDDLE_NAME varchar(40)='',
						    @run_SUFFIX, --@BORR_SUFFIX varchar(20)='',
						    @run_BIRTH_DATE, --@BORR_BIRTH_DATE datetime = null ,
							@r_BORR_MAIDEN_NAME,
							@civil_status,
							@BORR_GENDER, 
							@run_address,--	@BORR_ADDRESS varchar(MAX) = null ,
						    @BORR_AREA,
							@BORR_LENGTH_STAY,--	@BORR_LENGTH_STAY tinyint = null ,
						    @r_telNO,--	@BORR_TELNO varchar(20) = null ,
						    @r_MOBILENO,--	@BORR_MOBILENO varchar(27) = null ,
							@run_email,--	@BORR_EMAIL varchar(50) = null ,
							'',--	@BORR_REMARKS ntext = null ,
							1, --	@BORR_STATUS tinyint = null ,
							0x, --	@BORR_PICTURE image = null ,
							'',--	@BORR_NATIONALITY varchar(13) = null ,
							'',--	@BORR_COUNTRY varchar(13) = null ,
							'',--	@BORR_EDUCATION varchar(13) = null ,
							'',--	@BORR_COURSE varchar(13) = null ,
							'',--	@BORR_LAST_SCHOOL varchar(100) = null ,
							0,--	@BORR_YEAR_GRADUATED int = null ,
							0,--	@BORR_SMS_SOA bit = null ,
							0,--	@BORR_SMS_PAYMENT bit = null ,
							'',--	@BORR_ACCESS_CODE varchar(50) = null 
						    @co_borrower_new OUTPUT
		end

		    set @hcnt=@hcnt + 1
			exec co_borrower_comaker_insert @APPL_NEWCODE,
											'', --@AMKR_APP_COMAKER varchar(19),
											@co_borrower_new, --@AMKR_COMAKER_CODE varchar(19),
											@hcnt, --@AMKR_HIERARCHY tinyint = null,
											1, -- co borrower --
											@PN_NUMBER /** this is the saved PN **/


	   /*** fetch next record ***/   
	   fetch next from c_ref
		into @run_cis_no

	end

	/*** closing of cursor ****/
	close c_ref
	deallocate c_ref
end
--- Co Borrower ------------------------------------------------------------------------


return 0
/**
  exec leads_push_to_sofia
**/