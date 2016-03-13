drop table customers;
drop table payment;


create table customers
	(cname varchar(40) not null,
	address varchar(40) not null,
	primary key (cname, address));
 
grant select on customers to public;

CREATE TABLE payment
	(transaction_id int not null, 
	primary key (transaction_id));
 
grant select on payment to public;
 
 

insert into customers
values('Bennet Abraham', '6223 Bateman St. Berkeley, CA 94705');
 
insert into customers
values ('Majorie Green', '309 63rd St. #411, Oakland, CA 94618');
 
 
insert into payment
values('0736');
 
insert into payment
values('0877');