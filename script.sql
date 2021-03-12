CREATE table events(
    id int AUTO_INCREMENT PRIMARY key,
    event_type varchar(25),
    comment text,
    executed_at timestamp NULL DEFAULT NULL,
    executor_id int,
    order_id int,
    FOREIGN KEY(executor_id) REFERENCES users(id),
    FOREIGN KEY(order_id) REFERENCES orders(id)
)