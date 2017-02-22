#
# Kiva code challenge.
#
# Author: R. Westlund
# Version: 1.0
#

#
# Create database.
#
DROP DATABASE IF EXISTS rmwkiva;
CREATE DATABASE rmwkiva;
USE rmwkiva;


#
# For the sake of this challenge, attempting to generate a payment schedule on the same loan twice
# will generate an error because of the unique key. Simply TRUNCATE the table to start fresh.
#
# These tables would also normally include additional fields (e.g. creation and modify dates) not
# necessary for this challenge.
#
DROP TABLE IF EXISTS payments;

#
# Repayment requirements to each lender for a particular loan.
#
CREATE TABLE paymentDefaults (
  loan_id INT UNSIGNED NOT NULL,
  lender_id VARCHAR(32) NOT NULL,
  default_payment FLOAT NOT NULL DEFAULT 0,
  final_payment FLOAT NOT NULL DEFAULT 0,
  discrepancy FLOAT NOT NULL DEFAULT 0,
  UNIQUE INDEX pmkeyll (loan_id, lender_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

#
# Repayment details to each lender by month.
#
CREATE TABLE payments (
  loan_id INT UNSIGNED NOT NULL,
  lender_id VARCHAR(32) NOT NULL,
  month TINYINT UNSIGNED NULL DEFAULT NULL,
  amount FLOAT NULL DEFAULT NULL,
  UNIQUE INDEX pdkeyllm (loanid, lenderid, month),
  CONSTRAINT paid_fk_lnid FOREIGN KEY (loan_id) REFERENCES paymentDefaults (loan_id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT paid_fk_lrid FOREIGN KEY (lender_id) REFERENCES paymentDefaults (lender_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8;
