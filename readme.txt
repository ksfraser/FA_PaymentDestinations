/****************************************************************************
Name: ksf_payment_destinations
Free software under GNU GPL
*****************************************************************************/

WHAT DOES THIS MODULE DO?

This module allows you to have a direct invoice payment other than
cash that generates customer payments.  Example is payment types of
cheques, credit cards, etc.

We have 3 different payment processors we use for Credit card, and 
one which handles debit.  When at a trade show, this lets us choose 
the correct payment type, and have that payment accrue into
the correct "bank" account.  This will also allow us to have a cheque payment
that is accounted for differently than cash in our cash box (since the cash
float can be given as change and a cheque can't...)



Steps:
	Init Tables (install/upgrade step)
	Configure the payment destination table.

INSTALLATION:

1. FrontAccounting -> Setup -> Install/Activate Extensions

   Click on the icon in the right column corresponding to ksf_generate_catalogue

   Extensions drop down box -> Activated for (name of your business)

   Click on "active" box for ksf_generate_catalogue -> Update

2. FrontAccounting -> Setup -> Access Setup

   Select appropriate role click on ksf_generate_catalogue header and entry -> Save Role

   Logout and log back in

3. FrontAccounting -> Banking and General Ledger -> ksf_generate_catalogue

   Click on button -> Create Table
 
   Fill in details for connecting to the VTiger databases -> Update Mysql

----------------------------------------------------------

