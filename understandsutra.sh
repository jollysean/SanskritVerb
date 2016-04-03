# This code tries to evaluate different sUtras and effect of their non-existence on Panini's grammar.
# LIST1 is the array of verb numbers from our database $verbdata in function.php
# LIST2 is the array of lakAras to be tested.
# Define a timestamp function
timestamp() {
  date +"%d-%m-%Y %H:%M:%S"
}
timestamp
#LIST1 is the full list
LIST1=(10.0460 10.0328 01.0722 01.0901 01.0742 01.0902 10.0473 01.0092 10.0474 01.0155 01.0115 01.0999 01.0262 01.1000 01.0998 01.0215 10.0266 10.0021 07.0316 01.0332 10.0037 01.0287 01.0414 01.0403 01.0512 04.0071 01.0294 01.0038 02.0001 01.0403 02.0041 02.0042 04.0070 04.0072 02.0065 01.0063 01.0064 10.0471 01.0637 01.0536 10.0245 01.0438 01.0448 01.1031 01.0546 10.0145 01.0185 01.0232 10.0340 01.0256 10.0250 10.0447 10.0365 01.0057 01.0481 01.0667 10.0257 10.0367 01.0841 01.0593 01.0684 09.0059 05.0020 01.1030 04.0106 01.1029 02.0060 05.0029 10.0252 01.0237 05.0016 10.0376 01.0716 02.0012 10.0368 02.0011 02.0042 02.0041 02.0040 01.0148 01.0149 01.0163 01.0357 01.0065 07.0011 01.0670 10.0084 06.0167 06.0078 04.0022 09.0061 04.0038 01.0694 01.0150 01.0151 01.0207 01.0208 10.0009 02.0183 10.0008 02.0342 01.0587 01.0588 02.0010 01.0780 01.0695 01.0719 01.1102 01.0745 01.0136 01.0137 04.0135 06.0015 01.0244 06.0024 06.0014 01.0243 01.0852 01.0392 10.0385 06.0024 10.0271 07.0020 06.0023 06.0044 06.0045 01.0020 01.0650 01.0792 01.0840 01.0391 10.0430 01.0556 10.0023 02.0034 01.0779 01.0735 03.0017 05.0038 01.1086 06.0022 06.0016 01.0200 01.0201 08.0005 05.0027 04.0160 06.0040 06.0041 06.0007 09.0032 01.0203 01.0267 01.0300 01.0002 01.0705 01.0701 01.0129 01.0523 10.0014 02.0014 01.0095 01.0128 01.0893 01.0900 01.0099 01.0192 01.0265 01.0193 01.0330 01.0359 01.0385 06.0108 01.0417 01.0404 01.0517 01.0903 10.0240 01.0297 10.0385 01.0418 10.0067 01.0316 10.0456 01.0037 10.0456 10.0389 01.0881 01.0404 01.0531 01.0878 01.0073 01.0877 01.0440 01.0511 01.0435 01.0260 10.0470 10.0457 01.0061 01.0486 01.0664 10.0404 10.0093 01.0570 01.0571 02.0016 01.0781 02.0015 01.0996 01.0760 01.0194 10.0422 04.0058 01.0734 01.0710 03.0020 01.0338 01.0358 03.0021 01.1148 06.0080 10.0142 01.0602 01.0128 02.0127 06.0296 10.1103 04.0294 10.0037 04.0136 01.0096 01.0211 06.0095 01.0993 01.0226 01.0212 01.0255 10.0093 06.0222 10.0034 10.0226 06.0113 10.0435 06.0061 01.0363 10.0070 01.0397 10.0068 01.0303 10.0009 01.0362 10.0220 04.0012 10.0008 01.0045 09.0050 10.0007 10.0146 04.0310 10.0418 10.0157 01.0492 10.0158 06.0067 01.0021 01.0976 04.0126 09.0054 04.0125 10.0236 10.0443 06.0137 01.0254 10.0432 10.0225 10.0438 10.0211 01.0603 08.0010 01.1048 05.0007 06.0112 01.0682 06.0171 07.0010 10.0408 10.0278 01.0866 04.0140 06.0006 01.1145 06.0145 09.0018 09.0031 10.0155 01.0866 10.0437 01.0426 01.0616 01.0583 01.1064 01.0911 04.0007 09.0012 01.0558 01.0636 01.0912 01.0882 01.0879 01.0074 01.0876 01.0545 09.0001 01.0405 01.0213 06.0128 04.0086 01.0992 01.0913 01.0883 01.0880 01.0075 10.0162 04.0104 04.0157 01.0015 01.0076 04.0057 09.0058 01.0441 01.1113 01.0691 01.0518 01.0981 10.0113 01.0873 08.0003 10.0487 01.0935 04.0103 01.0510 10.0112 01.0986 10.0086 05.0033 01.0269 06.0143 08.0004 06.0005 04.0015 01.0648 05.0039 01.0270 01.0442 01.0443 09.0042 02.0031 07.0006 04.0087 09.0055 04.0154 01.0854 06.0070 01.0987 01.0649 01.1061 10.0416 02.0032 01.0559 01.0598 01.1133 01.1133 04.0159 01.0618 09.0067 01.0264 01.0266 01.0346 10.0127 10.0065 10.0066 01.0317 01.0052 01.1020 01.0261 01.0062 01.0487 01.0665 01.0626 09.0068 01.0782 01.0051 01.0339 07.0012 04.0066 06.0172 01.1104 01.0227 06.0119 10.0072 06.0068 01.0022 10.0413 10.0414 01.0617 01.0581 01.1060 10.0415 01.0633 01.0632 02.0055 10.0149 01.0279 01.0280 01.0886 10.0391 01.0068 01.0419 10.0399 01.0054 10.0204 01.1137 10.0177 01.0258 01.0059 10.0178 10.0179 01.0488 10.0449 01.0666 01.0723 10.0383 01.0627 10.0223 01.0457 01.0724 10.0425 01.1101 03.0026 01.0004 01.0736 01.1105 01.1100 06.0134 01.0230 06.0096 01.0231 06.0097 10.0436 10.0071 10.0069 01.0024 04.0014 09.0053 10.0147 01.1125 01.0461 04.0302 06.0042 06.0043 06.0131 01.0023 10.0180 01.0655 01.1043 10.0049 04.0217 01.0737 01.1087 01.0281 01.0282 04.0161 10.0441 06.0033 10.0146 09.0231 01.0427 01.0575 01.0698 01.1065 10.0417 01.0290 01.0036 10.0049 10.0362 09.0375 01.0717 10.0279 09.0071 10.0434 01.0224 01.0718 01.0738 01.0225 01.0228 01.0424 01.0428 01.0576 01.0699 01.1051 01.0180 01.0179 10.0297 01.0867 10.0248 10.0125 01.0292 10.0298 01.0740 01.0812 01.0502 01.0739 01.1106 01.0848 06.0115 06.0064 01.0505 01.0503 06.0071 01.0741 10.0251 04.0050 06.0065 01.0506 03.0152 10.0015 01.1088 08.0007 01.0504 01.0805 01.1075 01.0121 01.1107 02.0069 01.0098 01.0892 10.0084 02.0007 01.0217 10.0246 01.0331 01.0905 01.0312 10.0075 01.1003 01.1004 01.0914 10.0378 01.0071 10.0121 01.0465 01.0540 05.0540 10.0111 01.0550 01.0640 10.0274 06.0237 01.0814 10.0019 01.0491 01.0660 01.0966 10.0083 06.0097 01.1034 10.0405 01.0830 10.0120 01.1023 10.0325 10.0005 05.0124 01.0353 10.0192 01.0039 10.0459 10.0002 05.0034 06.0082 01.0611 10.0364 10.0305 01.1022 01.0446 10.0305 01.1021 10.0085 01.0591 06.0103 10.0104 10.0036 06.0126 01.0402 10.0164 01.0368 10.0081 01.0402 01.0469 01.0495 10.0130 10.0001 10.0091 01.0609 04.0053 10.0143 10.0026 01.0767 06.0049 10.0353 01.0615 01.0289 10.0275 01.0040 10.0114 10.0481 10.0359 10.0370 10.0062 01.0541 10.0078 01.1035 07.0003 10.0469 06.0105 06.0120 06.0154 06.0099 07.0352 10.0008 10.0354 10.0480 04.0041 10.0182 02.0066 01.0275 01.0276 01.0342 03.0025 04.0044 01.0463 01.0453 01.0542 10.0241 01.0813 01.0813 06.0018 01.0813 10.0015 01.0967 01.0464 01.0784 10.0243 04.0184 10.0108 02.0067 10.0324 01.0642 01.1096 10.0325 01.0678 01.0544 05.0035 01.0793 01.0643 01.1098 01.1110 01.0176 10.0325 06.0107 06.0148 10.0051 06.0106 01.0032 06.0052 10.0008 06.0371 04.0051 01.0776 01.0454 04.0027 10.0025 09.0346 01.0703 01.0731 01.1062 10.0118 09.0258 10.0043 09.0034 01.1109 01.0043 10.0347 01.1097 01.0885 01.0916 01.0965 01.0343 01.0543 01.0815 01.0815 06.0020 01.0815 01.0785 01.1036 09.0028 04.0026 10.0135 01.0968 01.0108 01.0109 01.0969 10.0196 10.0145 06.0189 04.0197 10.0098 04.0030 01.1123 01.0103 10.0254 01.0778 01.0124 01.0756 01.0743 01.0125 01.0158 01.0218 07.0022 01.0345 10.0064 10.0332 01.0314 08.0377 10.0001 10.0198 04.0350 10.0054 01.1140 04.0099 01.0551 10.0311 10.0201 01.0259 01.0060 10.0087 04.0109 01.0562 05.0022 01.0110 05.0023 01.1126 10.0154 01.0420 04.0017 01.0612 10.0081 06.0096 01.0613 01.0111 04.0018 10.0454 01.0646 02.0029 10.0044 01.0277 10.0285 10.0045 01.0278 06.0103 06.0116 01.0406 06.0058 10.0166 01.0309 10.0489 06.0001 06.0032 01.0470 06.0034 01.0474 09.0057 04.0156 01.0856 06.0033 01.0471 01.0475 06.0035 10.0160 01.0494 03.0022 01.0651 10.0088 04.0081 01.0807 01.0838 01.0407 10.0212 04.0047 01.0605 01.0768 06.0077 01.0750 08.0006 07.0009 06.0355 10.0028 10.0092 05.0351 04.0028 06.0030 06.0029 06.0031 04.0141 06.0075 07.0018 01.1124 01.0263 01.0421 01.0572 01.1141 10.0292 01.0748 01.0172 01.0102 01.0160 01.0072 01.0434 04.0269 10.0011 01.0173 10.0102 06.0221 01.0472 01.0476 01.0473 01.0477 01.1120 01.0104 01.0744 01.0159 06.0021 01.0219 01.0884 01.1156 01.0635 01.0167 06.0117 01.0652 10.0295 10.0193 01.1144 10.0317 10.0194 01.0874 01.0692 05.0030 01.0181 10.0472 01.0017 01.0008 04.0100 05.0026 01.0553 02.0068 10.0281 01.0629 05.0036 10.0110 04.0195 01.1146 03.0010 01.1079 02.0054 01.1149 01.1025 01.1041 01.0676 04.0249 10.0001 10.0230 06.0003 02.0005 04.0029 01.0693 02.0071 04.0045 01.1094 05.0476 10.0011 01.0653 10.0089 04.0082 01.0839 02.0004 04.0028 06.0835 01.0147 06.0356 10.0093 04.0036 06.0038 06.0357 10.0048 10.0358 06.0037 06.0039 01.0834 01.0920 09.0026 05.0037 01.1117 01.0573 01.1073 01.1143 04.0043 02.0035 01.0842 01.1053 01.0537 02.0049 01.0132 01.0120 01.0763 01.0322 01.0733 01.1095 06.0063 04.0094 09.0013 01.0083 01.1054 02.0003 01.1083 10.0083 01.0522 03.0024 01.0681 03.0011 01.0685 06.0142 01.0687 01.0677 03.0023 04.0031 06.0133 05.0009 01.0686 10.0303 01.0654 06.0020 05.0133 10.0010 09.0372 10.0303 01.0462 04.0048 10.0141 10.0140 10.0139 01.1047 06.0148 01.1115 01.0249 01.0250 10.0025 05.0388 09.0029 01.1050 01.0433 01.0634 01.1076 01.0766 01.1056 01.0245 01.0246 01.0529 10.0060 09.0270 01.0133 01.0764 01.0323 01.0253 06.0135 01.1093 01.0084 01.1055 01.0858 01.0251 01.0252 01.0521 10.0431 01.0933 01.0962 01.0765 01.1089 10.0082 01.0752 01.0142 01.0143 01.0347 10.0322 10.0018 01.0890 10.0309 01.0056 01.0070 09.0056 04.0155 01.0855 01.1136 01.0552 01.0058 10.0333 01.0972 04.0091 01.0714 04.0062 01.0007 01.0006 01.0712 02.0017 01.0747 03.0012 02.0018 01.1012 01.0069 01.0673 06.0087 10.0427 01.0823 01.0796 10.0209 01.1049 01.0600 01.0647 06.0132 02.0030 06.0002 06.0162 04.0010 09.0030 01.0921 01.1013 01.0704 10.0108 10.0107 01.0757 10.0024 01.1151 10.0153 01.0198 10.0394 10.0282 01.0333 01.0381 01.0507 10.0106 01.0315 10.0400 01.0979 10.0029 01.0982 10.0440 04.0065 01.0508 10.0060 01.0548 10.0485 01.0029 01.0478 01.0482 01.0658 10.0099 01.0973 10.0423 01.0621 10.0244 10.0401 01.1074 02.0051 10.0453 10.0098 06.0293 10.0141 10.0061 10.0046 10.0020 10.0287 02.0047 01.0348 01.0393 10.0186 10.0185 01.0307 01.0671 10.0173 06.0105 07.0015 01.0816 10.0050 04.0036 10.0017 01.0599 01.0644 10.0134 10.0455 10.0094 01.0367 06.0283 10.0035 01.0365 06.0114 10.0059 06.0133 10.0323 01.0370 10.0013 04.0306 01.0046 06.0072 01.0657 10.0090 01.0975 04.0121 10.0079 04.0065 09.0280 01.0797 04.0016 10.0079 09.0014 01.1121 10.0144 01.0557 10.0046 04.0334 10.0132 01.0657 10.0181 10.0131 01.0606 01.0769 03.0005 05.0013 06.0138 10.0024 07.0339 02.0025 02.0021 06.0054 06.0055 10.0028 01.0802 10.0004 09.0022 03.0004 01.0621 01.0577 01.0700 01.0817 01.1069 01.0527 01.0561 04.0119 04.0120 01.1119 06.0149 10.0027 01.0869 01.0870 02.0056 10.0039 09.0373 04.0002 01.1111 01.0800 09.0063 01.0706 01.0528 01.1005 01.1040 01.0729 09.0039 01.1112 04.0122 04.0009 01.0801 09.0064 01.0582 02.0050 01.0123 01.0955 01.0608 01.0594 01.0610 01.0622 01.0720 01.0383 01.0530 01.0378 01.0053 01.1128 10.0020 10.0044 09.0021 01.0638 01.0484 10.0300 01.0725 10.0173 10.0123 01.0974 10.0301 01.0726 10.0458 04.0112 10.0203 01.0320 01.0005 01.0732 01.0355 01.0066 10.0086 06.0095 01.0820 04.0123 01.0445 01.0126 10.0238 01.0177 04.0068 01.0994 01.1016 01.1017 04.0117 04.0129 10.0080 10.0299 01.0837 01.0836 06.0074 01.0821 01.0730 04.0116 01.0519 02.0039 10.0172 09.0038 01.1039 10.0033 10.0259 01.1153 10.0016 07.0290 01.0889 01.0344 01.0515 10.0077 01.0306 01.0012 10.0202 01.0662 01.0663 01.0661 01.0568 10.0224 01.0569 01.0791 04.0113 03.0019 02.0046 10.0428 10.0411 01.0509 01.0696 01.0711 01.0690 07.0002 01.0844 01.0067 03.0002 06.0153 07.0017 10.0382 10.0277 01.0001 01.0777 10.0255 03.0318 01.0137 10.1045 04.0006 01.0202 06.0129 04.0136 09.0024 01.1026 01.0715 01.0860 04.0138 01.0859 01.1037 06.0004 01.0520 04.0102 01.0985 06.0004 01.0205 01.0957 01.0958 09.0041 10.0213 01.0204 01.1027 01.1038 01.0959 01.1028 10.0330 01.0721 01.0140 01.0094 01.0141 01.0157 01.0117 01.0183 01.0195 06.0151 01.0197 01.0384 01.0516 01.0296 01.0305 10.0076 01.0361 01.0983 10.0105 04.0229 08.0233 04.0009 10.0073 10.0199 09.0047 01.0048 01.0044 01.0013 01.0639 01.0549 10.0151 01.0485 01.0659 01.0566 01.0567 01.0683 01.0585 01.0825 01.0788 01.0107 04.0131 01.0107 06.0151 10.0406 01.0831 04.0037 02.0057 03.0007 01.0762 10.0234 10.0381 01.1127 10.0384 10.0150 01.1042 05.0004 06.0017 10.0286 01.1008 01.1006 10.0158 04.0012 01.1010 10.0011 01.0672 06.0091 06.0165 01.0824 10.0466 01.0795 06.0079 01.1147 10.0032 04.0004 09.0361 01.0539 01.0595 01.0645 01.0166 10.0166 06.0272 01.0283 01.0196 01.0284 01.0366 06.0104 10.0101 01.0364 06.0060 01.0298 01.0369 01.0308 10.0268 01.0016 06.0069 01.0240 01.0656 09.0066 04.0130 10.0126 04.0095 09.0015 01.1122 10.0451 01.0607 10.0092 01.0770 06.0139 01.0754 10.0442 02.0386 10.0061 09.0052 06.0053 06.0057 10.0165 09.0051 01.1015 06.0161 10.0060 04.0387 01.0804 09.0025 01.1116 01.1009 01.1007 01.1011 01.0429 01.0578 10.0256 01.1078 01.0755 10.0168 10.0171 10.0169 01.0871 01.0222 01.0220 01.0327 01.0328 01.0223 01.0221 10.0170 01.0233 01.0329 01.0579 01.1052 10.0215 01.1157 10.0261 01.0030 10.0003 01.1135 01.1139 10.0119 04.0107 02.0044 01.1001 10.0027 09.0235 02.0011 01.0175 01.0242 10.0074 07.0338 04.0007 01.0031 04.0069 04.0148 01.0775 01.0702 01.0326 10.0329 01.0833 10.0262 01.0746 01.0144 10.0265 01.0894 10.0264 01.0145 10.0397 01.0153 10.0326 01.0112 10.0403 04.0063 01.1154 10.0462 01.0334 01.0387 01.0386 01.0904 01.0513 01.0680 01.0055 04.0090 01.0467 01.0479 01.1129 01.0989 01.0480 01.0436 01.0449 01.0554 10.0477 01.0810 10.0396 10.0122 01.0832 02.0052 01.0130 01.0118 01.0956 05.0018 04.0077 01.0713 05.0032 06.0140 01.0168 01.0169 01.0164 10.0004 07.0348 01.0679 06.0026 06.0156 04.0144 01.0790 06.0027 09.0035 04.0033 01.0319 02.0321 10.1114 10.0028 01.0847 06.0335 10.0152 10.0314 01.0849 10.0188 01.0389 01.0371 01.0400 01.0373 01.0375 02.0062 07.0001 04.0149 06.0155 04.0187 01.0789 10.0143 01.0995 10.0452 10.0479 01.0772 01.0085 01.0206 01.1002 01.0430 01.0447 01.0584 01.0707 01.1057 01.0412 01.0411 10.0219 10.0006 01.0146 10.0263 01.0895 01.0147 01.0154 10.0291 10.0327 01.0113 01.0182 01.0234 10.0463 10.0010 01.0271 06.0016 06.0011 10.0315 10.0048 01.0272 10.0465 01.0335 10.0010 01.0415 10.0331 10.0013 01.0468 01.1130 01.0439 01.0437 01.0450 01.0555 01.0483 01.0416 10.0210 01.1033 01.0811 10.0253 06.0011 02.0053 01.0131 01.0119 01.0273 01.0235 01.0274 10.0482 01.0170 06.0092 01.0171 01.0165 10.0267 10.0288 06.0169 04.0076 06.0157 02.0006 10.0034 04.0036 09.0343 01.0214 10.0049 10.0289 01.0351 10.0133 04.0109 01.0850 06.0284 01.0390 01.0851 04.0134 06.0110 01.0352 06.0111 10.0039 01.0372 10.0040 01.0374 01.0398 01.0401 01.0376 01.0047 06.0167 04.0150 04.0153 06.0025 10.0159 01.0493 09.0016 01.0771 10.0100 01.0431 10.0307 01.0080 10.0308 01.0188 01.0413 01.0291 01.0753 01.0138 01.0093 01.0100 01.0139 01.0156 01.0116 10.0058 02.0380 01.0285 01.0216 10.0227 10.0395 10.0461 01.0888 01.0337 01.0382 01.0514 10.0073 01.0377 10.0464 01.0295 10.0074 01.0304 10.0379 01.1164 01.0534 01.0915 08.0008 01.0533 01.0011 01.1158 01.0638 01.0984 01.0547 10.0390 01.0186 10.0484 10.0025 10.0156 01.0697 10.0175 01.0727 10.0300 01.0564 10.0054 01.0152 01.0456 01.0565 10.0301 01.0728 02.0075 01.0787 01.0106 10.0488 02.0111 01.1160 04.0273 10.0013 01.0106 10.0205 01.1159 02.0045 01.0761 01.0236 01.0321 10.0424 04.0056 04.0059 10.0426 01.0732 07.0005 10.0159 06.0304 03.0013 06.0009 07.0023 01.0354 01.0033 10.0059 02.0067 06.0013 07.0232 04.0168 06.0050 10.0138 10.0085 06.0094 06.0160 01.0794 09.0062 03.0014 10.0486 10.0207 01.0818 04.0124 02.0043 01.0209 10.0445 01.0178 04.0118 10.0837 05.0008 09.0299 10.0345 01.0045 01.0097 01.0688 10.0022 02.0024 07.0344 02.0023 06.0056 10.0055 04.0312 01.0862 10.0313 01.0863 04.0139 01.0803 10.0228 01.0836 06.0073 09.0019 09.0023 01.1161 01.1018 01.0034 01.1019 01.0425 10.0421 01.0614 01.0619 02.0072 01.0288 01.0819 01.0730 01.0620 01.1070 06.0013 01.0868 04.0078 10.0136 10.0478 10.0137 01.1024 04.0114 04.0008 04.0276 10.0115 01.1162 01.0247 01.0286 10.0109 01.0248 10.0483 01.0519 06.0012 09.0040 04.0035 04.0021 06.0127 10.0176 10.0174 09.0037 01.0829 04.0084 05.0017 01.0091 01.0189 01.0336 10.0392 10.0041 01.0394 10.0214 01.0906 01.0313 06.0164 01.0991 04.0064 01.1155 10.0239 10.0098 04.0218 10.0031 01.0489 01.0668 01.0977 01.0563 01.0455 01.0826 01.0827 01.0786 01.0828 01.0134 01.0324 01.1150 10.0052 02.0070 05.0003 01.0689 01.0174 01.0184 02.0019 01.0340 06.0089 01.0783 10.0014 07.0349 02.0026 10.0363 10.0320 01.0078 01.0444 10.0419 01.0601 01.0127 04.0061 01.0210 01.0590 10.0146 01.0395 01.0396 10.0147 01.0399 04.0088 06.0062 01.0077 10.0369 01.0853 06.0046 01.0500 01.0501 06.0047 10.0110 10.0101 04.0080 10.0444 04.0052 10.0102 01.0604 01.0773 01.0864 10.0260 01.1014 09.0021 01.0623 01.0580 01.1066 04.0040 01.0524 01.0325 01.0041 01.0042 01.0909 01.0596 01.0865 01.1108 01.1118 01.0857 01.0857 01.0089 01.0161 01.0907 10.0063 10.0409 01.0908 10.0019 10.0360 10.0046 09.0374 09.0048 01.0035 04.0101 01.0458 02.0048 01.0922 01.1044 01.0798 09.0003 01.1092 01.1067 01.0525 01.0090 01.0162 01.0910 01.0135 01.0122 01.0799 04.0059 10.0083 01.0081 01.0526 01.0101 01.0161 01.0190 01.0191 10.0393 10.0042 10.0043 10.0117 10.0115 01.0630 10.0053 01.0631 02.0064 01.1165 01.0843 01.0010 01.1059 01.0641 04.0004 01.0105 01.0105 02.0074 01.0898 10.0433 10.0467 01.0187 01.1152 01.0229 01.1142 01.0350 10.0128 10.0448 10.0448 06.0163 01.0990 05.0024 08.0002 01.0535 01.0466 10.0429 01.0963 04.0132 10.0030 10.0407 01.0759 01.0257 01.0490 01.0669 01.0628 02.0073 04.0341 10.0023 01.0988 05.0019 10.0051 10.0420 10.0032 10.0407 05.0002 09.0005 06.0170 01.0341 01.0049 01.0050 04.0089 01.0674 01.0498 01.0499 06.0090 04.0002 01.0079 05.0001 01.1091 02.0036 10.0475 10.0038 06.0066 04.0024 02.0025 04.0027 06.0144 10.0412 10.0450 01.0025 10.0242 01.0758 01.0586 01.0774 03.0018 01.1085 04.0075 06.0150 01.1138 01.0496 01.0497 01.0086 01.0624 01.0574 01.1063 04.0042 01.1134 09.0009 01.0452 09.0006 01.0009 09.0010 01.0872 01.0625 01.0891 10.0398 01.0532 01.0964 01.0451 09.0007 01.0970 05.0021 01.0422 04.0019 04.0020 02.0038 01.0199 10.0151 04.0190 01.0460 09.0008 10.0152 04.0191 05.0006 01.0751 06.0076 09.0017 10.0439 01.0423 01.1071 10.0468 01.1058 01.0749 01.0899 01.1077 06.0118 10.0446 04.0006 02.0047 04.0055 10.0097 02.0033 04.0005 04.0096 01.1072 01.0014 01.0003 10.0200 01.1032 05.0014 06.0158 10.0410 06.0123 06.0124 01.0560 10.0129 10.0056 10.0100 06.0247 01.0293 01.0379 06.0125 01.0380 10.0005 10.0004 01.0311 06.0121 01.0241 06.0122 01.0268 10.0058 01.1099 10.0057 01.0597 05.0015 01.0919 01.1082 01.0960 10.0216 01.0857 01.0088 01.0459 01.0861 04.0003 01.1090 01.0087 01.1068 01.0082 01.0114 01.0161 01.1131 01.0018 10.0336 01.0961 01.0936 02.0063 10.0402 10.0116 01.0019 01.0105 01.0105 10.0337 01.0028 04.0085 01.0845 01.1081 01.0349 01.0388 01.1132 02.0002 01.0538 01.0592 01.0589 01.0971 01.0822 10.0206 03.0008 03.0009 05.0019 10.0012 07.0366 01.0997 01.0356 01.0301 01.0675 06.0088 10.0208 03.0001 01.0408 06.0130 01.0302 01.0310 01.0239 01.0980 01.0978 01.0409 03.0016 01.1046 01.0806 04.0142 01.0808 09.0069 01.0299 01.0360 01.0318 01.0887 09.0070 01.0432 01.0708 01.0319 01.0410 02.0076 01.0918 01.0896 10.0163 01.0808 01.0026 03.0003 01.0238 01.0709 01.0897 10.0161 01.0809 01.0027 01.0917 01.1080 01.1084 01.1163)
#LIST3 is the abridge list having verbs falling in Gerard's database only. If you want to use it, rename it as LIST1 and code would work for you.
LIST3=(01.0742 10.0473 01.0155 01.0262 01.1000 01.0998 01.0215 10.0266 10.0021 07.0316 01.0332 01.0038 02.0001 04.0072 02.0065 10.0471 01.0536 10.0245 10.0447 10.0365 01.0057 10.0257 10.0367 01.0841 01.0684 09.0059 05.0020 04.0106 01.1029 02.0060 05.0029 05.0016 10.0376 02.0011 02.0042 02.0041 02.0040 01.0163 01.0065 07.0011 01.0670 10.0084 06.0167 06.0078 04.0022 09.0061 01.0694 01.0151 10.0009 02.0183 10.0008 02.0342 02.0010 01.0780 01.0695 01.0719 01.0745 04.0135 06.0024 06.0014 01.0243 10.0385 07.0020 06.0045 01.0792 10.0023 01.0735 03.0017 05.0038 01.1086 06.0022 01.0200 05.0027 04.0160 06.0007 01.0203 01.0267 01.0002 01.0418 10.0067 01.0316 01.0037 01.0531 01.0511 01.0435 01.0570 01.0781 02.0015 01.0996 01.0760 04.0058 01.0734 01.0602 01.0211 06.0095 01.0993 10.0093 06.0222 10.0070 01.0397 10.0068 01.0303 10.0009 01.0362 10.0220 01.0045 09.0050 10.0146 04.0310 01.0976 06.0137 01.0254 08.0010 05.0007 06.0171 07.0010 10.0278 01.0866 04.0140 06.0006 01.1145 06.0145 09.0018 01.0866 01.0879 01.0074 01.0545 09.0001 01.0405 01.0213 04.0086 01.0992 04.0104 04.0157 04.0057 09.0058 01.0981 10.0487 01.0935 04.0103 01.0510 01.0986 10.0086 05.0033 01.0269 06.0143 06.0005 04.0015 02.0031 07.0006 04.0087 09.0055 04.0154 01.0854 10.0066 01.0317 01.1020 01.0051 07.0012 04.0066 06.0172 01.0617 02.0055 10.0149 01.0279 10.0399 01.0054 01.1137 10.0177 01.0258 01.0059 10.0178 10.0449 01.0723 10.0383 01.0627 10.0223 01.0457 01.1101 03.0026 01.0736 01.0231 10.0071 10.0147 01.1125 01.0461 04.0302 06.0043 06.0131 01.1043 04.0161 06.0033 10.0146 09.0231 01.0036 10.0049 10.0362 09.0375 01.0717 10.0279 09.0071 01.1051 10.0297 01.0867 10.0248 10.0125 01.0292 01.0812 01.0741 10.0251 06.0065 01.0506 03.0152 10.0015 01.1088 01.0805 01.1075 02.0069 02.0007 01.0217 10.0246 01.0331 01.1003 01.0071 01.0540 05.0540 01.0640 10.0274 01.0966 10.0083 06.0097 10.0325 10.0005 05.0124 10.0192 01.0039 10.0459 10.0002 10.0081 01.0495 10.0130 10.0001 01.0767 01.0289 10.0275 10.0062 07.0003 10.0469 06.0099 02.0066 03.0025 04.0044 01.0463 01.0453 01.0542 01.0464 10.0243 04.0184 10.0108 02.0067 10.0324 01.0642 01.1096 10.0325 01.0678 01.0643 10.0008 06.0371 01.0454 04.0027 10.0025 09.0346 09.0258 10.0043 09.0034 01.0043 01.0885 01.0916 01.0965 04.0030 01.1123 01.0124 01.0756 01.0743 01.0218 07.0022 10.0064 08.0377 10.0001 04.0350 10.0054 01.1140 04.0099 10.0311 10.0201 01.0259 01.1126 10.0154 06.0001 06.0032 01.0470 10.0088 04.0081 07.0009 06.0355 10.0028 10.0092 05.0351 04.0028 04.0141 01.1124 01.1141 01.0434 04.0269 10.0011 10.0102 06.0221 01.1120 01.0744 01.0884 01.1156 01.0635 10.0295 10.0193 01.1144 01.0874 01.0692 05.0030 10.0472 04.0100 01.0553 10.0281 01.0629 10.0110 04.0195 01.1146 03.0010 01.1079 02.0054 01.1025 06.0003 02.0005 01.0693 04.0045 01.1094 05.0476 10.0011 04.0082 01.0839 02.0004 06.0835 06.0356 10.0093 04.0036 06.0357 10.0048 10.0358 01.0834 09.0026 01.0573 01.1143 01.0842 02.0049 01.1095 04.0094 02.0003 03.0011 01.0685 04.0031 01.0686 06.0020 05.0133 10.0010 09.0372 01.1047 06.0148 01.1115 10.0025 05.0388 06.0135 01.1093 01.0858 01.0933 01.0962 01.0765 01.1089 01.0752 01.0347 10.0322 10.0018 01.0890 01.0056 01.0070 01.1136 01.0058 04.0091 04.0062 01.0007 03.0012 01.0069 01.1049 06.0132 02.0030 06.0002 06.0162 04.0010 01.1151 01.0333 01.0381 01.0507 01.0979 10.0029 01.0982 04.0065 10.0244 01.1074 02.0051 06.0293 10.0020 10.0287 02.0047 10.0173 06.0105 07.0015 10.0017 10.0013 04.0306 04.0121 10.0079 04.0065 09.0280 01.0797 09.0014 01.1121 10.0144 01.0557 03.0005 05.0013 06.0138 10.0024 07.0339 02.0025 10.0028 01.0802 10.0004 09.0022 03.0004 10.0027 01.0869 02.0056 10.0039 09.0373 04.0002 01.1111 01.1112 04.0122 04.0009 01.0801 09.0064 01.0608 01.0594 01.0610 01.0720 10.0044 09.0021 10.0123 01.0974 01.0005 04.0068 01.0994 01.1016 01.0836 06.0074 02.0039 01.1039 10.0033 10.0259 01.1153 10.0016 07.0290 01.0515 10.0077 01.0306 01.0012 10.0202 01.0791 04.0113 03.0019 02.0046 01.0696 01.0711 07.0002 01.0844 03.0002 06.0153 07.0017 10.0382 10.0277 01.0001 01.0777 10.0255 03.0318 01.0137 01.0860 04.0138 04.0102 01.0985 01.0205 01.0957 10.0330 01.0721 06.0151 01.0305 10.0076 01.0361 10.0105 04.0229 08.0233 04.0009 10.0073 09.0047 01.0048 01.0044 01.0013 01.0831 04.0037 02.0057 03.0007 10.0384 05.0004 01.1008 01.1006 10.0158 04.0012 06.0091 06.0165 10.0466 01.0795 06.0079 01.1147 10.0032 04.0004 09.0361 01.0595 10.0166 06.0272 10.0268 01.0016 09.0066 04.0095 10.0451 01.0770 06.0139 10.0442 02.0386 10.0061 09.0052 06.0053 09.0051 01.1015 06.0161 10.0060 04.0387 01.0804 10.0256 01.1078 01.0328 01.1157 10.0261 01.0030 01.1135 01.1139 10.0119 04.0107 02.0044 01.1001 10.0027 09.0235 02.0011 10.0074 07.0338 04.0007 04.0069 10.0329 01.0833 01.0746 04.0063 01.1154 01.0904 01.0513 01.0055 04.0090 01.1129 01.0989 10.0477 01.0832 02.0052 01.0956 05.0018 04.0077 05.0032 06.0140 10.0004 07.0348 04.0144 01.0790 01.0319 02.0321 01.0847 06.0335 10.0152 02.0062 07.0001 04.0187 01.0789 10.0143 01.0995 10.0452 10.0219 10.0006 10.0263 01.0895 01.0154 10.0291 10.0327 01.0113 01.0182 06.0011 01.0468 01.1130 01.0439 01.0437 10.0210 01.1033 01.0811 10.0253 01.0235 06.0092 06.0169 04.0076 06.0157 02.0006 10.0034 04.0036 01.0390 06.0110 10.0040 01.0374 01.0398 01.0401 06.0167 04.0153 06.0025 09.0016 10.0307 01.0080 10.0308 01.0188 01.0753 10.0058 02.0380 01.0216 10.0227 10.0379 01.1164 01.0534 01.0915 08.0008 01.0533 01.0011 01.1158 01.0984 10.0484 10.0025 01.0564 01.0152 02.0075 02.0111 01.1160 04.0273 01.1159 02.0045 01.0236 04.0059 10.0426 07.0005 03.0013 06.0009 07.0023 02.0067 06.0013 07.0232 04.0168 06.0050 10.0138 06.0160 09.0062 03.0014 02.0043 01.0209 10.0837 05.0008 09.0299 10.0022 02.0024 07.0344 10.0055 04.0312 01.0862 10.0313 01.0863 01.0803 10.0228 01.0836 06.0073 01.0288 01.0868 04.0078 01.0247 01.0286 10.0109 10.0483 06.0012 04.0021 01.0829 04.0084 05.0017 01.0091 06.0164 01.0991 04.0064 01.1155 10.0098 04.0218 01.0977 01.0563 01.0827 01.0828 02.0070 01.0689 01.0783 10.0014 07.0349 02.0026 01.0601 04.0061 01.0210 04.0088 06.0046 01.0500 01.0501 06.0047 04.0080 09.0021 04.0101 01.0458 01.1044 01.1092 01.0122 01.0799 04.0059 10.0083 02.0064 01.0843 01.0187 01.1152 01.1142 06.0163 01.0990 08.0002 01.0535 01.0466 04.0341 10.0023 01.0988 05.0019 01.0049 01.0050 04.0089 05.0001 01.1091 02.0036 10.0475 06.0066 02.0025 04.0027 06.0144 10.0450 01.0025 10.0242 03.0018 01.1085 04.0075 06.0150 01.1138 01.0574 01.1134 09.0009 01.0452 01.0625 01.0532 01.0451 09.0007 02.0038 01.0460 09.0017 10.0439 01.1077 02.0047 04.0055 10.0097 02.0033 04.0096 01.0014 01.0003 10.0200 01.1032 06.0158 06.0124 10.0100 06.0247 01.0293 01.0379 06.0121 10.0058 01.1099 05.0015 01.0919 01.1082 01.0857 01.1090 01.1131 01.0018 10.0336 01.0961 01.0936 02.0063 04.0085 01.0845 01.0388 01.1132 02.0002 01.0822 03.0008 03.0009 05.0019 10.0012 07.0366 01.0997 03.0001 01.0302 01.0310 03.0016 01.1046 01.0806 04.0142 02.0076 01.0808 01.0026 03.0003 01.0027 01.0917 01.1080 01.1084)
# LIST4 is the list of representative verbs (verbs dealt with in SK)
LIST4=(01.0001 01.0002 01.0003 01.0004 01.0005 01.0006 01.0008 01.0009 01.0017 01.0018 01.0020 01.0038 01.0040 01.0047 01.0050 01.0052 01.0054 01.0056 01.0057 01.0070 01.0097 01.0099 01.0136 01.0200 01.0267 01.0215 01.0222 01.0223 01.0233 01.0238 01.0239 01.0262 01.0269 01.0286 01.0359 01.0379 01.0420 01.0434 01.0453 01.0461 01.0462 01.0510 01.0511 01.0535 01.0540 01.0545 01.0546 01.0561 01.0593 01.0642 01.0677 01.0736 01.0737 01.0742 01.0756 01.0789 01.0792 01.0812 01.0842 01.0843 01.0844 01.0862 01.0865 01.0866 01.0868 01.0955 01.0979 01.0985 01.0988 01.0990 01.0991 01.1020 01.1043 01.1044 01.1045 01.1050 01.1051 01.1084 01.1081 01.1085 01.1086 01.1091 01.1092 01.1117 01.1124 01.1128 01.1131 01.1134 01.1137 01.1138 01.1140 01.1143 01.1157 01.1160 01.1161 01.1162 01.1163 01.1165 02.0001 02.0002 02.0003 02.0007 02.0183 02.0026 02.0011 02.0321 02.0034 02.0039 02.0040 02.0041 02.0044 02.0059 02.0060 02.0386 02.0062 02.0063 02.0065 02.0066 02.0067 02.0068 02.0069 02.0070 02.0071 03.0001 03.0002 03.0004 03.0318 03.0007 03.0009 03.0010 03.0011 03.0012 03.0025 04.0249 04.0010 04.0029 04.0040 04.0044 04.0065 04.0059 04.0091 04.0218 04.0106 04.0107 05.0001 05.0124 05.0006 05.0008 05.0012 05.0018 05.0020 06.0001 06.0004 06.0009 06.0121 06.0139 06.0145 06.0272 07.0001 07.0011 07.0018 07.0316 08.0377 08.0010 09.0001 09.0006 09.0014 09.0034 09.0054 09.0055 09.0067 09.0071 10.0001 10.0017 10.0027 10.0028 10.0118 10.0005 10.0155 10.0307 10.0391) 
LIST1=(01.0001 01.0002 02.0001 02.0183 03.0001 03.0002 04.0001 04.0027 05.0001 05.0020 06.0001 06.0371 07.0001 07.0012 08.0001 08.0008 09.0001 09.0299 10.0001)
LIST2=(law liw luw lfw low laN ASIrliN viDiliN luN lfN)
number=1
rm -rf sutrarelations
mkdir sutrarelations
echo "Starting the analysis of verb forms generated by tiGanta.php"
echo "The suspect forms will be stored in suspectverbforms.txt file."
for VALUE1 in "${LIST1[@]}"
do
	echo "$number - processing verb number $VALUE1 started at $(timestamp)"
	for VALUE2 in "${LIST2[@]}"
	do
		echo "started $VALUE2 lakAra analysis"
		php panini.php ${VALUE1} ${VALUE2}
		while read name
		do
			echo "started ignoring $name and storing in sutrarelations/difflog1.txt"
			php panini.php ${VALUE1} ${VALUE2} $name
		done < sutrarelations/vidhi.txt
	done
	((number++))
done
#php scripts/slp-dev.php suspectverbforms.txt suspecverbforms_deva.txt
#python comparewithdb.py
rm -rf scripts/sutrarelations.html
cd scripts
echo '<html><body>' >> ../sutrarelations/sutrarelations.html
php sutrarelationdisplay.php >> ../sutrarelations/sutrarelations.html
echo '</body></html>' >> ../sutrarelations/sutrarelations.html
