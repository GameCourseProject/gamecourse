import os

ROOT_PATH = os.path.dirname(os.path.abspath(__file__))
IMPORTED_FUNCTIONS_FOLDER = os.path.join(ROOT_PATH, "imported-functions")
COURSE = None
RULES_PATH = None
AUTOSAVE = False

test_mode = False
award_list = []
majors_alameda = ["MEIC-A", "MEMec", "MEEC", "LEIC-A", "LEIC", "LEGM", "LENO", "LMAC", "MA", "MEAer", "MEAmbi", "MEBiol", "MEBiom", "MEC", "MEFT", "MEM", "MEQ", "MBioNano", "MBiotec", "MENO", "MEFarm", "MEGM", "MECD", "MEGE", "MEGIE", "MEP", "MMA", "Microbiologia", "MOTU", "MPSR", "MQ", "MSIDC"]
majors_tagus = ["MEIC-T", "MEGI", "METI", "LEIC-T", "LEE", "LEGI", "LETI", "MEE"]
metadata = None