# Performance Discussion

## Current flow

posts
loop over posts
loop over stats
save data posts \* stats times

loop over stats
calculate data

Very memory inefficient. CPU is actually fine.
PHP has to do go through a lot of data. It makes a lot of shallow copies.

From ChatGPT

```
When passing object as arguments does PHP make shallow copies or just passes a pointer?
ChatGPT
When passing an object as an argument in PHP, it behaves as a shallow copy rather than passing a pointer. In PHP, objects are typically handled by reference. When an object is passed as an argument to a function or assigned to another variable, a reference to the original object is created, and any modifications made to the object within the function or the new variable will affect the original object.
```

## What would I do instead?

I don't think this approach is good to start with. I see 3 solutions from little to a lot of implementation

1. Pass objects by reference. I think that should help with performance. Otherwise keep everything the same.
2. Get rid of this abstraction and handle posts in one loop. We can make a fatter structure that can handle all statisics at once. Yes, ugly. Yes, difficult to extend. Yes, difficult to work with. But performance.
3. Why do it in code at all? Why not use SQL? I am not the most SQL guy I'll admit but! I know that you can have pretty complex queries with SQL and it will be faster with analytical types of queries than in code.
