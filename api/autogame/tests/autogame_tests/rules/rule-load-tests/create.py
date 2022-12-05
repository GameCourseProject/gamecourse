

with open('qm.txt', 'r') as file:
	data = file.read()

num = 1

for i in range(0, num):
	file_name = 'rule_' + str(i) + '.txt'
	with open(file_name, 'w') as rule:
		rule.write(data[:17] + ' ' + str(i) + data[17:408] + ' ' + str(i) + data[408:])