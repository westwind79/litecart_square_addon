import sys,json;
targets = json.load(sys.stdin)[sys.argv[1]]
for target in targets:
    print(target)
