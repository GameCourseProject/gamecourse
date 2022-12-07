rule: rule name
	when:
		raise Exception("Kaboom")
	then:
		raise Exception("Kaboom")