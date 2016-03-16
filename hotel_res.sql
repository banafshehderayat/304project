drop table customers;
drop table payment;
drop table rooms;
drop table location;

create table customers
	(cname varchar(40) not null,
	address varchar(40) not null,
	primary key (cname, address));
 
grant select on customers to public;

CREATE TABLE payment
	(transaction_id int not null, 
	primary key (transaction_id));
 
grant select on payment to public;
 
CREATE TABLE location
	(location_address varchar(40) not null,
	 primary key (location_address));

grant select on location to public;

CREATE TABLE rooms
	(room_number int not null,
	 location_address varchar(40) not null,
	 type char(20),
	 max_occupancy int,
	 primary key (room_number, location_address),
	 foreign key (location_address) references location);

grant select on rooms to public;


insert into customers
values('Bennet Abraham', '6223 Bateman St. Berkeley, CA 94705');
 
insert into customers
values ('Majorie Green', '309 63rd St. #411, Oakland, CA 94618');
 
 
insert into payment
values('0736');
 
insert into payment
values('0877');

insert into location
values ('123 Main Street');

insert into location
values ('111 UBC');

insert into rooms
values ('1', '123 Main Street', 'Penthouse Suite', '8');

insert into rooms
values ('2', '111 UBC', 'King Room', '4');