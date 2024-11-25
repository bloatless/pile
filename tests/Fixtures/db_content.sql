-- Insert some test data

INSERT INTO logs (source,message,`level`,level_name,context,channel,extra,created_at) VALUES
    ('MyProjectName','Some error occoured',400,'error','{"exception":{"class":"My\\Fancy\\Classname","message":"Invalid value<\/code><script>alert(''foo'');<\/script>","code":"42","file":"\/framework\/src\/Foo\/Bar\/Classname.php:1337"}}','dev','[]','2019-11-05 17:44:26.0'),
    ('Test','Some debug message...',100,'debug','[]','MyLogger','[]','2023-04-20 08:03:29.0');
