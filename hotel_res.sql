drop table customers;
drop table card_payment;
drop table cash_payment;
drop table payment;
drop table employee;
drop table rooms;
drop table location;

create table customers
	(cname varchar(40) not null,
	 address varchar(40) not null,
	 cid number not null unique,
	 primary key (cname, address));
 
grant select on customers to public;

CREATE TABLE payment
	(transaction_id number not null, 
	 amount number not null,
	 primary key (transaction_id));
 
grant select on payment to public;

CREATE TABLE cash_payment 
	(transaction_id number not null,
	 primary key (transaction_id),
	 foreign key (transaction_id) references payment);
grant select on cash_payment to public;

CREATE TABLE card_payment 
	(transaction_id number not null,
	 card_number char(16),
	 primary key (transaction_id),
	 foreign key (transaction_id) references payment);
grant select on card_payment to public;
 
CREATE TABLE location
	(location_address varchar(40) not null,
	 primary key (location_address));

grant select on location to public;

CREATE TABLE rooms
	(room_number number not null,
	 location_address varchar(40) not null,
	 type char(20),
	 max_occupancy int,
	 primary key (room_number, location_address),
	 foreign key (location_address) references location);

grant select on rooms to public;

CREATE TABLE employee
	(employee_id number not null,
	 name varchar(40),
	 location_address varchar(40),
	 manager_id int,
	 primary key (employee_id),
	 foreign key (location_address) references location,
	 foreign key (manager_id) references employee);

grant select on employee to public;




insert into customers
values('Bennet Abraham', '6223 Bateman St. Berkeley, CA 94705', 54);
 
insert into customers
values ('Majorie Green', '309 63rd St. #411, Oakland, CA 94618', 21);

insert into payment
values(0736, 40);

insert into payment
values(0877, 30.31);

insert into payment
values(4455, 400);

insert into payment
values(6655, 530);

insert into cash_payment
values(0736);
 
insert into cash_payment
values(0877);

insert into card_payment
values(4455, '1111222233334444');

insert into card_payment
values(6655, '5555666677778888');

insert into location
values ('123 Main Street');

insert into location
values ('111 UBC');

insert into rooms
values ('1', '123 Main Street', 'Penthouse Suite', '8');

insert into rooms
values ('2', '111 UBC', 'King Room', '4');

insert into employee
values ('1' , 'James Bond' , '123 Main Street', null);

insert into employee
values ('2' , 'Austin Powers' , '111 UBC', 1);
