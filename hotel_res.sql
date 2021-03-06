drop SEQUENCE e_seq;
drop table employee;
drop SEQUENCE pay_seq;
drop SEQUENCE res_seq;
drop table reserves;
drop table cash_payment;
drop table card_payment;
drop table payment;
drop SEQUENCE cu_seq;
drop table customers;
drop table rooms;
drop table location;

create table customers
	(cname varchar(40) not null,
	 address varchar(40) not null,
	 cid number not null unique,
	 password varchar(255) not null,
	 primary key (cname, address));

	CREATE SEQUENCE cu_seq;
	CREATE OR REPLACE TRIGGER cu_bir
	BEFORE INSERT ON customers
	FOR EACH ROW
	WHEN (new.cid IS NULL)
	BEGIN
  		SELECT cu_seq.NEXTVAL
  		INTO   :new.cid
  		FROM   dual;
	END;
	/

grant select on customers to public;

CREATE TABLE payment
	(transaction_id number,
	 amount number not null,
	 primary key (transaction_id));

	CREATE SEQUENCE pay_seq;
	CREATE OR REPLACE TRIGGER pay_bir
	BEFORE INSERT ON payment
	FOR EACH ROW
	WHEN (new.transaction_id IS NULL)
	BEGIN
  		SELECT pay_seq.NEXTVAL
  		INTO   :new.transaction_id
  		FROM   dual;
	END;
	/

grant select on payment to public;

CREATE TABLE cash_payment
	(transaction_id number,
	 primary key (transaction_id),
	 foreign key (transaction_id) references payment(transaction_id) ON DELETE CASCADE);

	CREATE OR REPLACE TRIGGER cashpay_bir
	BEFORE INSERT ON cash_payment
	FOR EACH ROW
	WHEN (new.transaction_id IS NULL)
	BEGIN
  		SELECT pay_seq.CURRVAL
  		INTO   :new.transaction_id
  		FROM   dual;
	END;
	/

grant select on cash_payment to public;

CREATE TABLE card_payment
	(transaction_id number,
	 card_number char(16),
	 primary key (transaction_id),
	 foreign key (transaction_id) references payment(transaction_id) ON DELETE CASCADE);

	CREATE OR REPLACE TRIGGER cardpay_bir
	BEFORE INSERT ON card_payment
	FOR EACH ROW
	WHEN (new.transaction_id IS NULL)
	BEGIN
  		SELECT pay_seq.CURRVAL
  		INTO   :new.transaction_id
  		FROM   dual;
	END;
	/

grant select on card_payment to public;

CREATE TABLE location
	(location_address varchar(40) not null,
	 primary key (location_address));
	grant select on location to public;

CREATE TABLE rooms
	(room_number number not null,
	 location_address varchar(40) not null,
	 type char(20),
	 max_occupancy number,
	 cost_per_day number not null,
	 primary key (room_number, location_address),
	 foreign key (location_address) references location);
 	grant select on rooms to public;

CREATE TABLE employee
	(employee_id number not null,
	 name varchar(40) not null,
	 location_address varchar(40) not null,
	 manager_id int,
	 password varchar(255) not null,
	 primary key (employee_id),
	 foreign key (location_address) references location,
	 foreign key (manager_id) references employee);

	CREATE SEQUENCE e_seq;
	CREATE OR REPLACE TRIGGER e_bir
	BEFORE INSERT ON employee
	FOR EACH ROW
	WHEN (new.employee_id IS NULL)
	BEGIN
  		SELECT e_seq.NEXTVAL
  		INTO   :new.employee_id
  		FROM   dual;
	END;
	/

grant select on employee to public;

CREATE TABLE reserves
	(reservation_id number,
     name varchar(40) not null,
	 address varchar(40) not null,
	 location_address varchar(40) not null,
	 room_number int not null,
	 transaction_id int,
	 start_date date not null,
	 end_date date not null,
	 primary key (reservation_id),
	 foreign key (name, address) references customers(cname, address),
	 foreign key (location_address) references location,
	 foreign key (room_number, location_address) references rooms,
	 foreign key (transaction_id) references payment ON DELETE CASCADE);

	CREATE OR REPLACE TRIGGER res_tx_bir
	BEFORE INSERT ON reserves
	FOR EACH ROW
	WHEN (new.transaction_id IS NULL)
	BEGIN
  		SELECT pay_seq.CURRVAL
  		INTO   :new.transaction_id 
  		FROM   dual;
	END;
	/
    
    CREATE SEQUENCE res_seq;
    CREATE OR REPLACE TRIGGER res_id_bir
	BEFORE INSERT ON reserves
	FOR EACH ROW
    WHEN (new.reservation_id IS NULL)
    BEGIN
        SELECT res_seq.NEXTVAL
        INTO :new.reservation_id
        FROM dual;
    END;
	/

grant select on reserves to public;



--> Password is hashed versions of the string 'ccc' <---
insert into customers
values('Bennet Abraham', '6223 Bateman St. Berkeley, CA 94705', null,
	'$2y$10$GU5jDoFrdUDG90aKuMfNRel4JadmBhmxISoWtj60LABsoBpr2j8sW');

insert into customers
values ('Majorie Green', '309 63rd St. #411, Oakland, CA 94618', null,
	'$2y$10$GU5jDoFrdUDG90aKuMfNRel4JadmBhmxISoWtj60LABsoBpr2j8sW');

insert into customers
values ('Elliot', '010 Robot St', null,
	'$2y$10$GU5jDoFrdUDG90aKuMfNRel4JadmBhmxISoWtj60LABsoBpr2j8sW');

insert into payment
values(0736, 40);

insert into payment
values(0877, 30.31);

insert into payment
values(4455, 400);

insert into payment
values(6655, 530);

insert into payment
values(1122, 530);

insert into payment
values(9900, 530);

insert into payment
values(23, 30.31);

insert into payment
values(24, 400);

insert into payment
values(25, 530);

insert into payment
values(26, 530);

insert into payment
values(27, 530);

insert into payment
values(null, 40);

insert into cash_payment
values(null);

insert into payment
values(null, 454);

insert into cash_payment
values(0736);

insert into cash_payment
values(0877);

insert into card_payment
values(4455, '1111222233334444');

insert into card_payment
values(6655, '5555666677778888');

insert into card_payment
values(null, '9999666677778888');

insert into location
values ('123 Main Street');

insert into location
values ('111 UBC');

insert into rooms
values (1, '123 Main Street', 'Penthouse Suite', 8, 400);

insert into rooms
values (2, '123 Main Street', 'King Room', 4, 200);

insert into rooms
values (3, '123 Main Street', 'Queen Room', 4, 175);

insert into rooms
values (4, '123 Main Street', 'Twin Room', 2, 100);

insert into rooms
values (5, '123 Main Street', 'Single', 1, 50);

insert into rooms
values (6, '123 Main Street', 'Single', 1, 50);

insert into rooms
values (1, '111 UBC', 'King Room', 4, 150);

insert into rooms
values (2, '111 UBC', 'Queen Room', 4, 150);

insert into rooms
values (3, '111 UBC', 'Single', 1, 100);

insert into rooms
values (4, '111 UBC', 'Single', 1, 100);

--> Password is hashed versions of the string 'eee' <---
insert into employee
values (null , 'Sarah Parallel', '123 Main Street', null,
	'$2y$10$C4/RI35R3Th/E/dTMW6OgeY9sEaCN.qNJZAW151XEPZABk6f68npu');

insert into employee
values (null , 'James Bond' , '123 Main Street', null,
	'$2y$10$C4/RI35R3Th/E/dTMW6OgeY9sEaCN.qNJZAW151XEPZABk6f68npu');

insert into employee
values (null , 'Austin Powers' , '111 UBC', 1,
	'$2y$10$C4/RI35R3Th/E/dTMW6OgeY9sEaCN.qNJZAW151XEPZABk6f68npu');

insert into employee
values (null , 'Emily Terran' , '111 UBC', 2,
	'$2y$10$C4/RI35R3Th/E/dTMW6OgeY9sEaCN.qNJZAW151XEPZABk6f68npu');

insert into reserves
values (null, 'Bennet Abraham', '6223 Bateman St. Berkeley, CA 94705', '123 Main Street', 1, 0736, '01-APR-16', '05-APR-16');

insert into reserves
values (null, 'Bennet Abraham', '6223 Bateman St. Berkeley, CA 94705', '123 Main Street', 2, 23, '01-APR-16', '05-APR-16');

insert into reserves
values (null, 'Bennet Abraham', '6223 Bateman St. Berkeley, CA 94705', '123 Main Street', 3, 24, '01-APR-16', '05-APR-16');

insert into reserves
values (null, 'Bennet Abraham', '6223 Bateman St. Berkeley, CA 94705', '123 Main Street', 4, 25, '01-APR-16', '05-APR-16');

insert into reserves
values (null, 'Bennet Abraham', '6223 Bateman St. Berkeley, CA 94705', '123 Main Street', 5, 26, '01-APR-16', '05-APR-16');

-- insert into reserves
-- values (null, 'Bennet Abraham', '6223 Bateman St. Berkeley, CA 94705', '123 Main Street', 6, 27, '01-APR-16', '05-APR-16');

insert into reserves
values (null, 'Elliot', '010 Robot St', '111 UBC', 1, 6655, '06-APR-16', '09-APR-16');

insert into reserves
values (null, 'Elliot', '010 Robot St', '111 UBC', 2, 0877, '13-APR-16', '15-APR-16');

insert into reserves
values (null, 'Elliot', '010 Robot St', '111 UBC', 3, 1122, '18-APR-16', '19-APR-16');

insert into reserves
values (null, 'Elliot', '010 Robot St', '111 UBC', 4, 9900, '20-APR-16', '23-APR-16');
