# **WEB-BASED BUSINESS-TO-BUSINESS(B2B) CROP DISTRIBUTION AND LOGISTICS MANAGEMENT SYSTEM WITH REAL-TIME TRACKING** 

**A Capstone Project Proposal Presented to the Faculty of the Information and Communications Technology Program STI College - General Santos, Inc.** 

**In Partial Fulfilment of the Requirements for the Degree Bachelor of Science in Information Technology** 

**Elnes Jake F. Gabales Gabriel Andrei M. Lopez Ray Manuel C. Pineda Iver Jude E. Relox** 

**April 2026** 

## **ENDORSEMENT FORM FOR PROPOSAL DEFENSE** 

## **TITLE OF RESEARCH:** 

**Web-Based Business-to-Business (B2B) Crop Distribution and Logistics Management System with Real-Time Tracking** 

## **NAME OF PROPONENTS:** 

Elnes Jake F. Gabales Gabriel Andrei M. Lopez Ray Manuel C. Pineda Iver Jude E. Relox 

In Partial Fulfilment of the Requirements for the degree Bachelor of Science in Information Technology has been examined and is recommended for Proposal Defense. 

## **ENDORSED BY:** 

Ival Carl H. Delizo **Capstone Project Adviser** (t> 

## **APPROVED FOR PROPOSAL DEFENSE:** 

Jolina M. Migriño, LPT Jhon Dell B. Aristales **Capstone Project Coordinator                   Capstone Project Coordinator** chi 

**NOTED BY:** 

Anthony Mark F. Silong, MIT **Program Head** 

**April 2026** 

_STI College_ _**–** General Santos, Inc._ 

ii 

## **APPROVAL SHEET** 

This capstone project proposal titled **Web-Based Business-to-Business (B2B) Crop Distribution and Logistics Management System with Real-Time Tracking** , prepared and submitted by **Elnes Jake F. Gabales, Gabriel Andrei M. Lopez, Ray Manuel C. Pineda** , and **Iver Jude E. Relox** , in partial fulfillment of the requirements for the degree of  Bachelor  of  Science  in  Information  Technology,  has  been  examined  and  is recommended for acceptance and approval. 

Ivan Carl H. Delizo **Capstone Project Adviser** 

Accepted and approved by the Capstone Project Review Panel in partial fulfillment of the requirements for the degree of Bachelor of Science in Information Technology 

Nur Ali M. Padasan, LPT, MIT **Panel Member** 

Dan Henly P. Sales **Panel Member** 

Anthony Mark F. Silong, MIT **Lead Panelist** 

## **Noted:** 

Jolina M. Migriño, LPT **Capstone Project Coordinator** 

Anthony Mark F. Silong, MIT **Program Head** 

**April 2026** 

_STI College_ _**–** General Santos, Inc._ 

iii 

## **TABLE OF CONTENTS** 

||**Page**|
|---|---|
|Title Page|i|
|Endorsement form for Proposal Defense|ii|
|Approval Sheet|iii|
|Tables of Contents|iv|
|List of Tablest|v|
|List of Figures|vi|
|**Introduction**|1|
|Project Context|1|
|Purpose and Description|3|
|Objectives|4|
|Scope and Limitations|5|
|Review of Related Literature/Studies/Systems|9|
|**Methodology**|17|
|Technical Background|20|
|Requirements Analysis|25|
|Requirements Documentation|26|
|Design of Software, System, Product, and/or Processes|27|
|**References**|31|
|**Appendices**|34|
|A. List of Tables|37|
|B. Figures|45|
|C. Calendar of Activities|50|
|D. Evaluation Forms and Tools||
|E. Resource Persons||
|F. Proof of Data Gathering||
|G. Transcript of Interview||
|H. Revision List||
|I. Adviser Acceptance Form||
|J. Accomplishment and Consultation Forms||
|K. Letter||



_STI College_ _**–** General Santos, Inc._ 

iv 

|L. Questionnaires||
|---|---|
|M. Personal Technical Vitae||
||34|
|**List of Tables**||
|Table 1 Software Tools|37|
|Table 2 Hardware Specification||
||34|
|**List of Figures**||
|Figure 1 Methodology|37|
|Figure 2 Context Flow Diagram||
|Figure 2.1 Data Flow Diagram||
|Figure 2.2 Entity Relationship Diagram||
|Figure 3 Gantt Chart||
|Figure 4 Survey Questionaire for Logistics Coordinators||
|Figure 5 Survey Questionnaire for Farmers||
|Figure 6 Survey Questionnaire for Drivers||
|Figure 7 Research Correspondent Form||



_STI College_ _**–** General Santos, Inc._ 

v 

## **INTRODUCTION** 

## **Project Context** 

Agriculture plays a vital role in supporting food supply, market distribution, and economic activity, e. The distribution of crops, fruits, and vegetables from farms to various markets and collection points depends on effective coordination between producers, distributors, and transport providers to ensure timely and organized product flow (Accorsi et al., 2022; Devaux et al., 2021). Efficient distribution systems improve market access for farmers and support supply chain performance by linking production areas to demand points (Candel, 2023). In addition, traceability systems enhance transparency by allowing stakeholders to monitor product movement and access important distribution information across the supply chain (Aung & Chang, 2021; Krstić et al., 2023). 

Despite its importance, crop distribution in the Philippines faces several challenges related to coordination and system organization. Distribution activities often rely on fragmented communication and manual planning, resulting in less efficient allocation of deliveries, repeated trips, and underutilized transport resources (Zhang et al., 2025). The presence of multiple intermediaries and limited coordination between stakeholders further affects the smooth flow of goods and reduces farmers’ access to broader markets (Chong et al., 2022; Quintana et al., 2022). These issues are more evident in rural areas where distribution networks are not fully structured, making it difficult to organize deliveries across multiple farms and destinations. Studies suggest that structured coordination systems can improve distribution planning and workflow among stakeholders (Zhang et al., 2021). 

Another key challenge in crop distribution is the limited visibility of product movement during delivery operations. Once goods are in transit, stakeholders have limited 

_STI College_ _**–** General Santos, Inc._ 

1 

access to real-time information about delivery status, location, and progress (Ivanov, 2022). This lack of visibility affects coordination across the distribution process and reduces the ability to respond to delays or disruptions (Lubag et al., 2023). Research shows that realtime tracking, monitoring, and information sharing significantly improve both logistics performance and distribution coordination (Bigliardi et al., 2022; Dovbischuk, 2023). Furthermore, route planning and mapping technologies support distribution efficiency by enabling the consolidation of multiple pickup points and optimizing delivery paths (Laporte, 2021; Saberi et al., 2022; Chuyen & Rajagopal, 2025). 

In the Philippine context, the absence of a centralized and digital distribution platform continues to affect agricultural operations. Many stakeholders still rely on traditional communication methods such as phone calls and face-to-face coordination, which can lead to delays, miscommunication, and less organized distribution processes (Queiroz & Wamba, 2021). Digital platforms have been shown to improve coordination and information flow by allowing stakeholders to share data, monitor transactions, and manage distribution activities more effectively (Granillo et al., 2023; Olivarese et al., 2023). Even simplified digital systems can enhance both logistics and distribution by improving coordination and supporting better decision-making (Prajapati et al., 2022; Heliyon, 2024). Additionally, platforms like HARVEST demonstrate how digital solutions can strengthen connections between producers and buyers, highlighting the importance of integrated distribution systems (Carasco et al., 2026). 

In response to these challenges, this study proposes the development of a WebBased Business-to-Business (B2B) Crop Distribution and Logistics Management System with Real-Time Tracking for General Santos City. The system focuses on crops, fruits, and vegetables  and  is  designed  to  manage  drop-off  locations,  delivery  routes,  monitor deliveries in real time, and maintain digital records. By integrating distribution planning, route-based pickup coordination, and real-time tracking, the system aims to improve product flow, reduce unnecessary trips, and enhance coordination among farmers, logistics 

_STI College_ _**–** General Santos, Inc._ 

2 

coordinators, and drivers (Bafe, 2023; Zhang et al., 2025). To support field operations, selected features will also be accessible through a Progressive Web Application (PWA), providing mobile-friendly access while maintaining the system’s web-based structure. 

## **Purpose and Description** 

In response to the existing challenges in crop transportation and logistics operations in General Santos City and Polomolok, this capstone project proposes the development of a Web-Based Business-to-Business (B2B) Crop Distribution and Logistics Management System with Real-Time Tracking. The system is intended to address common logistical problems such as fragmented communication, less efficient coordination, underutilized vehicles, and limited visibility during delivery operations. Through a centralized digital platform, the project aims to improve coordination, transparency, and efficiency in the transport of crops, fruits, and vegetables from pickup points to designated drop-off locations. 

The proposed system will function as a web-based platform accessible through a standard web browser. It will allow authorized users, specifically the Administrator, Logistics Coordinator, Driver, and Farmer, to manage user accounts, register drop-off locations, plan delivery routes, monitor vehicle movement in real time, and maintain digital delivery records. By replacing manual coordination methods such as phone calls, text messages, and face-to-face communication, the system is expected to reduce delays, miscommunication,  and  unnecessary  trips.  The  system  may  also  be  deployed  as  a Progressive Web Application (PWA) to provide drivers with faster, mobile-friendly, and installable access using supported browsers while preserving the web-based nature of the system. 

A key feature of the system is its route planning capability. This allows users to determine delivery paths along a selected route, making it possible to consolidate pickups 

_STI College_ _**–** General Santos, Inc._ 

3 

within a single trip. This function supports better truck utilization, improved scheduling, and more efficient transport operations. In addition, the real-time tracking feature provides visibility into delivery progress, helping authorized users monitor ongoing transport activities and respond more quickly to operational issues or delays. 

The system also includes delivery records and reporting functions to store important logistics information such as route history, timestamps, delivery status, and vehicle activity.  These  records  can  be  used  for  monitoring,  evaluation,  and  operational documentation. However, the proposed system is limited only to logistics coordination and monitoring within General Santos City and Polomolok, and does not cover crop trading, payment processing, or farm production management. 

Overall, the purpose of the project is to provide a practical and localized digital solution that will modernize crop distribution and logistics coordination in **General Santos City and Polomolok** . By integrating mapping, route planning, tracking, and reporting into one web-based system, the study aims to support a more organized, reliable, and efficient agricultural transport process for registered stakeholders. 

## **Objectives** 

The general objective of this study is to develop a Web-Based Business-to-Business (B2B) Crop Distribution and Logistics Management System with Real-Time Tracking that will improve coordination and efficiency in the transportation of crops, fruits, and vegetables from pickup points to drop-off locations in General Santos City and Polomolok. 

Specifically, it aims to: 

1. Develop  a  web-based  platform  for  managing  registered  users,  including Administrators, Logistics Coordinators, Drivers, and Farmers. 

_STI College_ _**–** General Santos, Inc._ 

4 

2. Develop a route planning feature with an interactive map that identifies pickup points located along a selected delivery route to support consolidated pickup of crops, fruits, and vegetables. 

3. Create a real-time monitoring interface with interactive map visualization that allows authorized users to track delivery progress and vehicle movement through a web browser. 

4. Develop a delivery records and reporting feature that stores operational history and generates basic reports on delivery status, route utilization, and vehicle activity. 

5. Ensure that the system is deployable as a Progressive Web Application (PWA) to provide mobile-friendly access for drivers and support in-app messaging between farmers and logistics coordinators within General Santos City and Polomolok. 

## **Scope and Limitations** 

## **Scope** 

The scope of this study covers the design and development of a Web-Based Business-to-Business (B2B) Crop Distribution and Logistics Management System with Real-Time Tracking intended for crop logistics operations within General Santos City and Polomolok. The system focuses only on the distribution and transport coordination of crops, fruits, and vegetables. It is intended for registered users involved in these operations, specifically the Administrator, Logistics Coordinator, Driver, and Farmer. The system is primarily web-based and accessible through standard web browsers. It also includes an interactive  map  and  in-app  messaging  feature  that  allows  Farmers  and  Logistics Coordinators  to  communicate  regarding  transportation  coordination  and  pricing negotiations. In addition, selected driver-side functions may also be made available through a Progressive Web Application (PWA) to provide more convenient mobile access during field operations. 

## **1. User Registration and Management Module:** 

_STI College_ _**–** General Santos, Inc._ 

5 

This module allows the registration and management of system users, including the Administrator, Logistics Coordinator, Driver, and Farmer. It stores basic account and contact information and allows the Administrator to update user details, reset passwords, and manage user access to the platform. 

## **2. Pickup, Drop-Off, and Route Planning Module** 

This module allows authorized users to manage pickup and drop-off points located within General Santos City and Polomolok. Each location contains basic details and geographic coordinates displayed on an interactive digital map to support location visualization and logistics planning. The module also allows authorized users to create and manage delivery routes along selected paths, enabling  crops,  fruits,  and  vegetables  from  multiple  pickup  points  to  be consolidated and collected within a single trip. Through this feature, the system helps improve vehicle utilization, reduce unnecessary travel, and support more efficient transportation operations. 

## **3. Delivery Monitoring, Tracking, Records, and Reports Module** 

This module allows authorized users to monitor delivery progress and vehicle movement in real time through a web browser using interactive map visualization and tracking features. It provides visibility into route progress, delivery status, and transport activities to support better coordination during logistics operations. The module also stores digital records of completed deliveries, including pickup and drop-off details, route history, timestamps, and delivery status. For field operations, Drivers may access selected monitoring and delivery functions  through  a  Progressive  Web  Application  (PWA),  which  provides installable and mobile-friendly access on supported devices while maintaining the web-based nature of the system. 

## **4. In-app Messaging Module:** 

_STI College_ _**–** General Santos, Inc._ 

6 

This module provides an in-app messaging feature that allows Farmers and Logistics  Coordinators  to  communicate  directly  within  the  system  regarding delivery coordination and transportation arrangements. Through this feature, users may  discuss  and  negotiate  transportation  pricing,  delivery  schedules,  pickup arrangements, and other logistics-related concerns in real time. 

## **5. Progressive Web Application (PWA) Module** 

This module includes a Progressive Web Application (PWA) intended for Drivers to provide mobile-friendly and installable access during field operations. Through the PWA, Drivers can access selected system features using supported mobile devices without requiring a fully native mobile application. The PWA includes  responsive  design,  installable  application  capability,  and  convenient access to delivery monitoring, interactive map tracking, and route updates while on the field. 

## **Limitations** 

## **1. Limited Geographic and Operational Coverage:** 

The system is intended only for crop distribution and logistics operations within General Santos City and Polomolok. It focuses solely on the transportation and coordination of crops, fruits, and vegetables and does not support intercity, regional, or nationwide logistics operations, nor other agricultural products such as livestock, fisheries, or processed goods. 

## **2. Dependence on Manual Data Entry:** 

The accuracy of system information depends on the correct manual input of data by authorized users, including user information, pickup points, drop-off points, delivery details, and route updates. The system does not automatically validate all entered information or detect unregistered locations and actual harvest availability. 

## **3. Limited Route Planning and Tracking Features:** 

_STI College_ _**–** General Santos, Inc._ 

7 

Although the system provides route planning, interactive map visualization, and real-time delivery monitoring, it does not include advanced technologies such as AI-based route optimization, predictive traffic analysis, weather-based rerouting, IoT sensor integration, or automated delivery verification. Delivery monitoring depends on available internet connection, GPS data, and user-generated status updates. Specifically, the system sequences multi-stop pickups by sorting pending locations chronologically or by closest proximity based on baseline geographic coordinates. Consolidated pickups are limited strictly by the predefined volume or weight  capacities  assigned  to  each  delivery  vehicle  profile.  Ad-hoc  route adjustments or nearby pickup groupings are restricted to a fixed predefined radius along the active transit path and require review and approval from the Logistics Coordinator interface. Furthermore, real-time vehicle monitoring depends entirely on standard web browser client-side GPS location tracking and user-driven status updates transmitted through standard internet connectivity. 

## **4. Limited Communication and Pricing Functions:** 

The in-app messaging feature is intended only for communication and coordination between Farmers and Logistics Coordinators regarding transportation arrangements  and  pricing  discussions.  The  system  does  not  process  online payments, financial transactions, or real-time pricing adjustments. Transportation pricing is based only on predefined factors such as distance, delivery volume, fuel cost, and crop type for estimation and coordination purposes. 

## **5. Limited PWA and Device Compatibility:** 

The Progressive Web Application (PWA) is intended only for Drivers and provides selected mobile-friendly system functions related to delivery monitoring, interactive map tracking, and route updates. Since the system remains primarily web-based, performance and available features may vary depending on device compatibility, browser support, and internet connectivity. Some advanced features commonly available in fully native mobile applications may not be supported. 

_STI College_ _**–** General Santos, Inc._ 

8 

## **Review of Related Literature/Studies/Systems** 

One important concept in agricultural logistics is traceability. Traceability systems allow stakeholders to monitor the movement of agricultural products from pickup locations to distribution points, thereby improving transparency and product safety. According to Aung and Chang (2021), traceability systems enable stakeholders to track food products throughout the supply chain, ensuring that information regarding product origin and distribution history can be accessed when needed. Similarly, Bosona and Gebresenbet (2023) explained that traceability improves logistics coordination by enabling stakeholders to access important product information throughout the supply chain. Some advanced traceability systems use technologies such as blockchain, Internet of Things (IoT) devices, and automated sensors. However, simpler digital systems can still provide effective monitoring by recording shipment details and delivery information. 

Another important development in logistics management is the digital transformation of supply chains. Digital logistics platforms allow organizations to share information, monitor shipments, and coordinate transportation activities more efficiently. Queiroz and Wamba (2021) noted that digital transformation enhances supply chain collaboration by improving communication and data accessibility among stakeholders. 

In addition to digital platforms, research also highlights the importance of efficient logistics coordination and transportation planning. Agricultural supply chains often face challenges related to fragmented transportation systems and limited information sharing. Accorsi et al. (2022) emphasized that integrating digital logistics systems can improve coordination among supply chain stakeholders and support more sustainable transportation operations.  Their  findings  show  that  digital  logistics  platforms  help  improve  route planning, resource utilization, and communication among agricultural stakeholders. 

Transportation efficiency is also closely related to route optimization and logistics planning. Laporte (2021) discussed the vehicle routing problem and emphasized the importance of route optimization techniques in improving transportation efficiency. By identifying optimal delivery routes, logistics systems can reduce transportation costs, 

_STI College_ _**–** General Santos, Inc._ 

9 

improve  vehicle  utilization,  and  minimize  delivery  delays.  These  approaches  are particularly  useful  in  agricultural  logistics  where  delivery  vehicles  must  coordinate multiple farms and distribution points during transport operations. 

Another key area of research involves logistics monitoring and supply chain resilience. Supply chain resilience refers to the ability of logistics systems to maintain operations despite disruptions. Ivanov (2022) explained that supply chain resilience depends largely on effective monitoring and collaboration among stakeholders. Monitoring systems provide visibility into logistics operations, allowing organizations to respond quickly to delays or disruptions in transportation activities. 

Food safety and product quality are also important considerations in agricultural logistics. Monitoring systems help ensure that products are handled and transported properly during distribution. Lin et al. (2023) explained that monitoring and traceability systems are essential for maintaining product safety in food distribution networks. These systems allow organizations to track shipment details and identify potential issues during the distribution process. Although advanced technologies such as IoT sensors are often used for monitoring product conditions, basic digital systems can still support logistics transparency by recording transportation activities and delivery information. 

Zhang et al. (2025) examined how digital transformation enhances transportation coordination and resilience in agricultural supply chains. Their findings revealed that improved  coordination  and  information  sharing  between  stakeholders  significantly strengthen logistics efficiency and reduce disruptions in distribution processes. While their study incorporates advanced digital transformation concepts, the results suggest that even basic digital systems can improve coordination among supply chain participants. In relation to this, the proposed system focuses on developing a web-based B2B crop distribution and logistics management system within General Santos City and Polomolok, providing essential coordination and tracking features without implementing AI, ML, IoT, or blockchain technologies. 

Recent studies also highlight the importance of collaboration platforms in businessto-business logistics systems. Digital B2B platforms enable organizations to coordinate 

_STI College_ _**–** General Santos, Inc._ 

10 

transportation operations and share logistics information more effectively. Schoenherr and Speier-Pero  (2021)  explained  that  B2B  digital  platforms  strengthen  supply  chain collaboration  by  allowing  organizations  to  exchange  logistics  data  and  coordinate distribution activities. 

Another technological development in logistics management is the use of datadriven and real-time monitoring technologies. Ben-Daya et al. (2021) discussed how IoT devices such as GPS trackers and sensors can collect real-time data regarding vehicle movement and transportation conditions. These technologies enhance logistics visibility and support real-time monitoring of delivery operations. 

Research on agricultural value chains also highlights the importance of effective logistics  coordination.  Devaux  et  al.  (2021)  emphasized  that  improved  logistics coordination can enhance farmers’ access to markets and increase overall supply chain efficiency. Efficient transportation systems allow producers, distributors, and logistics providers to collaborate more effectively in delivering agricultural products to market destinations. Similarly, Candel (2023) explained that well-coordinated logistics systems contribute to the stability and sustainability of food supply chains by ensuring that agricul 

Similarly, research on sustainable agro-food supply chains highlights the role of digital platforms in improving logistics efficiency. Prajapati et al. (2022) discussed how e- commerce and digital systems contribute to better coordination in agricultural supply chains  by  facilitating  communication  between  producers,  distributors,  and  other stakeholders. Their study emphasized that digital logistics platforms help streamline distribution processes and improve overall supply chain performance. Although their research explores  broader technological  applications,  it  supports  the  idea  that  even simplified web-based systems can enhance logistics operations. This aligns with the proposed study, which aims to improve crop distribution and logistics coordination in General Santos City and Polomolok through a centralized web-based platform. 

Furthermore, recent literature in agricultural systems and logistics management also highlights the importance of digital solutions in addressing inefficiencies in supply chain operations. A study published in _Heliyon (2024)_ discussed how digital logistics 

_STI College_ _**–** General Santos, Inc._ 

11 

systems  improve  monitoring,  coordination,  and  information  flow  in  agricultural distribution networks. The research emphasized that digital platforms enable stakeholders to track product movement and manage logistics activities more effectively. While some systems integrate advanced technologies, the study indicates that even basic digital platforms can significantly improve logistics performance. Similarly, the proposed system focuses on implementing core logistics functions such as order management, delivery tracking, and coordination for crop distribution within General Santos City and Polomolok. 

In the agricultural sector, Chen et al. (2025) examined agricultural e-commerce logistics  capabilities  and  found  that  digital  logistics  systems  significantly  improve coordination between farmers, distributors, and logistics providers. Their findings indicate that digital platforms enable stakeholders to monitor shipments and manage distribution operations more efficiently. 

Similarly,  Olivarese  et  al.  (2023)  highlighted  that  digital  logistics  platforms improve  transparency  and  coordination  in  agri-food  supply  chains  by  allowing stakeholders  to  track  product  movement  and  logistics  activities.  These  findings demonstrate  that  digital  platforms  are  essential  tools  for  improving  supply  chain coordination and logistics efficiency. 

Dovbischuk (2023) demonstrated that logistics platforms equipped with real-time tracking  capabilities  improve  operational  reliability,  particularly  in  rural  logistics environments where transport visibility is limited. 

Osuna-Velarde et al. (2024) examined Logistics 4.0 systems that integrate tracking technologies and IoT devices. Their research found that connected logistics systems significantly enhance delivery transparency and responsiveness by enabling real-time monitoring of transportation operations. 

Chuyen and Rajagopal (2025) emphasized the importance of visualization and modeling tools in logistics networks. Their research demonstrated that mapping tools help identify delivery routes and enable consolidation of multiple pickup points along a single transport path. 

_STI College_ _**–** General Santos, Inc._ 

12 

Similarly, studies on digital mapping and route visualization systems published in Saberi, S. et al. (2022) highlight how spatial technologies improve delivery planning by identifying pickup locations along transportation routes. These mapping technologies support logistics planning by helping organizations visualize transportation networks and coordinate delivery operations more effectively. 

Krstić et al. (2023) emphasized the importance of traceability systems in agri-food supply chains, explaining that digital tracking platforms strengthen transparency and accountability  by  allowing  stakeholders  to  record  product  movement  and  monitor distribution processes. Together, these studies show that digital platforms and traceability systems play an important role in improving coordination, visibility, and monitoring within supply chains. 

Earlier research on fleet management systems also supports the importance of tracking technologies in logistics management. Bosona and Gebresenbet (2021) noted that vehicle monitoring systems improve logistics coordination by providing information about vehicle movement and transportation progress. Similarly, fleet tracking systems discussed in earlier logistics research (ScienceDirect, 2021) show that real-time monitoring tools provide valuable insights into delivery routes and vehicle activities. 

Efficient  coordination  remains  a  critical  factor  in  improving  supply  chain performance, particularly in systems involving multiple stakeholders. Zhang et al. (2021) examined how coordination and digital integration influence operational efficiency within supply chains and found that structured systems significantly improve communication and workflow  between  participants.  Their  findings  indicate  that  when  stakeholders  are connected through a centralized platform, delays are reduced and overall distribution processes become more efficient. This highlights the importance of implementing systems that facilitate coordination rather than relying solely on traditional or fragmented methods. In line with this perspective, the proposed study introduces a web-based B2B crop distribution and logistics management system in General Santos City and Polomolok, enabling farmers, logistics coordinators, and drivers to coordinate deliveries effectively. Although the study by Zhang et al. (2021) considers broader digital transformation 

_STI College_ _**–** General Santos, Inc._ 

13 

concepts, its findings support the idea that even basic web-based systems can enhance logistics coordination without requiring advanced technologies such as AI, ML, IoT, or blockchain. 

Additionally,  agricultural  supply  chains  in  the  Philippines  continue  to  face challenges  related  to  less  efficient  logistics  systems  and  the  presence  of  multiple intermediaries. Quintana et al. (2022) examined the impact of logistics on marketing margins in the Philippine agricultural sector and found that the involvement of middlemen significantly  increases  costs  and  reduces  farmers’  profit  margins.  The  study  also highlighted that limited access to efficient logistics systems and market information prevents farmers from directly connecting with buyers, resulting in inefficiencies in distribution. In response to these challenges, digital platforms and logistics systems have been identified as potential solutions to improve coordination and streamline distribution processes. 

The study of Chong et al. (2022) examined how logistics affects the marketing margin in the Philippine agricultural sector and found that inefficient transportation, storage, and distribution systems significantly increase costs while reducing farmers’ profits. Their findings showed that the presence of multiple intermediaries and poor coordination in the supply chain can lead to higher product prices and limited market access for producers. The study further emphasized that improving logistics efficiency can reduce unnecessary costs, enhance product flow, and promote a more transparent distribution process. This is relevant to the present study because the proposed system is designed to improve  logistics  coordination,  support  real-time  monitoring,  and  provide  a  more organized and transparent crop distribution process in General Santos City and Polomolok. 

Lubag  et  al.  (2023)  highlighted  the  importance  of  digital  dashboards  and monitoring systems in improving logistics visibility. Their study found that real-time monitoring tools allow logistics managers to track transportation activities and respond more effectively to delays or operational issues. 

Similarly, Granillo et al. (2023) highlighted that digital logistics technologies enhance transparency and traceability in agri-food supply chains by enabling stakeholders 

_STI College_ _**–** General Santos, Inc._ 

14 

to track transactions and product movement more efficiently. These technologies improve coordination among producers, distributors, and logistics providers, thereby strengthening supply chain reliability. 

The Philippine Food Chain Logistics Masterplan 2023–2033 outlines the need for a more efficient, modern agricultural logistics system that integrates farmers, transporters, ‑ processors, and markets to improve food distribution, reduce post harvest losses, and lower logistics costs. This national strategy stresses the importance of digital transformation and collaboration among stakeholders to address systemic inefficiencies in distribution and supply chain coordination. A web‑based system that provides route optimization, real‑time tracking,  and  delivery  management  directly  supports  these  goals  by  enhancing transparency and operational efficiency within the agricultural value chain (Bafe, 2023). 

HARVEST: A Mobile Platform for Direct Farmer to Buyer Transactions by Carasco et al. (2026) highlights the potential of mobile platforms to improve agricultural logistics by directly connecting farmers and buyers, reducing reliance on intermediaries. This platform facilitates smoother transactions, enhances communication, and improves market access for farmers by providing real-time updates and transaction records. While HARVEST focuses primarily on market coordination rather than transportation, its use of digital tools for real-time monitoring and communication aligns with the goals of the WebBased B2B Crop Distribution and Logistics Management System proposed in this study. Both  platforms  share  a  common  goal  of  improving  coordination  and  efficiency  in agricultural operations, demonstrating that even simplified digital solutions can make a significant impact on logistics without requiring advanced technologies like IoT or blockchain (Carasco et al., 2026). 

Research on agricultural supply chains highlights the need to improve coordination and efficiency through structured distribution systems. Balanay and Guinancias (2025) examined the agricultural supply chain in the Caraga Region and found that while local systems can be profitable and resilient, they are often constrained by weak logistics coordination and limited market linkages. These limitations reduce the ability of farmers to reach broader markets and maximize distribution efficiency. This observation supports 

_STI College_ _**–** General Santos, Inc._ 

15 

the need for improved coordination platforms. In response, the proposed study introduces a web-based B2B crop distribution and logistics management system within General Santos City, enabling better interaction among farmers, logistics coordinators, and drivers without relying on advanced technologies. 

General Santos City (GSC) is a key agricultural hub in Mindanao with significant production of high-value crops. The General Santos City Green City Action Plan (2022) identifies challenges in agricultural distribution due to logistical inefficiencies, limited infrastructure, and coordination gaps among farmers, transporters, and markets. The plan emphasizes the need to support smarter logistics solutions that improve the flow of goods and strengthen linkages across the value chain. The proposed web-based logistics system aligns with the Green City Action Plan’s objectives by promising data-driven decision making, better route and delivery coordination, and transparent communication among stakeholders (Bimp-Eaga, 2022). 

_STI College_ _**–** General Santos, Inc._ 

16 

## **METHODOLOGY** 

The study will use the Agile Scrum methodology to develop the system through iterative sprints, where each module is designed, developed, tested, and improved based on user feedback. Scrum is suitable for this project because it supports flexibility in handling dynamic and complex systems such as a Web-Based Business-to-Business (B2B) Crop Distribution and Logistics Management System with Real-Time Tracking. Since the system involves multiple stakeholders with varying needs, Scrum enables continuous refinement of features such as route planning, and real-time delivery tracking, ensuring the system adapts to user requirements and operational challenges. 

**Figure 1.** 

**Scrum Methodology** 

The proponents used the Scrum Framework as the foundation for the development of the system. Scrum is a subset of Agile methodology that follows an iterative and flexible approach, allowing continuous improvement throughout the system development process. Instead of following a rigid sequence, Scrum divides the project into manageable iterations 

_STI College_ _**–** General Santos, Inc._ 

17 

called Sprints, which promote adaptability, faster development, and efficient problemsolving. This approach emphasizes collaboration, continuous feedback, and the delivery of incremental system features that align with user needs, which is crucial for addressing the unique logistical challenges in General Santos City and Polomolok. 

The Scrum framework consists of key components such as the Product Backlog, Sprint Planning, Daily Scrum, Sprint Review, and Sprint Retrospective. These elements guided the development of the system and ensured that each module, such as user management, delivery coordination, and real-time tracking, was developed systematically and improved continuously. The Scrum Master played an important role in facilitating the process, ensuring effective communication among team members, resolving obstacles, and keeping the development aligned with project goals. 

During the initial phase, the Product Backlog was created by gathering system requirements and identifying key challenges in agricultural logistics operations. The proponents conducted interviews, surveys, and observations involving logistics operators and stakeholders to understand issues such as less efficient route planning, lack of coordination, and the absence of real-time delivery tracking. These findings were used to define and prioritize system features, forming the basis of the Product Backlog. 

Each Sprint began with Sprint Planning, where selected tasks from the Product Backlog were organized into a Sprint Backlog. The team focused on developing specific system  components  per  Sprint,  such  as  delivery  route  management,  and  tracking functionalities. Daily Scrum meetings were conducted to monitor progress, address issues, and ensure that all team members remained aligned with the Sprint goals. 

During the development phase, the system was built incrementally, with each module developed, tested, and refined within its respective Sprint. The system utilized web-based technologies to ensure accessibility and ease of use for stakeholders. Key features, route planning, delivery monitoring, and reporting tools, were implemented progressively to ensure functionality and usability. 

_STI College_ _**–** General Santos, Inc._ 

18 

At the end of each Sprint, a Sprint Review was conducted where developed features were presented to the project adviser and selected users for evaluation. Feedback gathered during this phase was used to improve the system in subsequent Sprints. Testing was integrated throughout the development process, including unit testing, integration testing, and user acceptance testing, to ensure system reliability and performance. 

Following the development of core functionalities, the system proceeded to the deployment phase. The proponents implemented the system in a controlled environment, conducted user orientation, and provided technical  support  to ensure proper usage. Adjustments and optimizations were made based on user feedback and actual system performance. 

Finally, Sprint Retrospectives were conducted after each Sprint to evaluate team performance, identify challenges encountered, and improve development practices. This continuous evaluation allowed the system to evolve effectively and ensured that it meets the logistical coordination needs of agricultural stakeholders while improving efficiency, transparency, and real-time monitoring in crop distribution operations. 

**Data Flow Diagram (DFD):** It visually represents how data moves through the system, showing the interaction between users and processes such as user management,  mapping, delivery coordination, real-time tracking, and report generation within the Web-Based Business-to-Business (B2B) Crop Distribution and Logistics Management System in General Santos City and Polomolok. 

**Context Flow Diagram (CFD):** It provides an overview of the system’s boundaries, illustrating how external entities such as administrators, logistics coordinators, farmers, and drivers interact with the system. The CFD shows the main data inputs and outputs exchanged between users and the system, defining its scope and connections to external processes. 

**Entity Relationship Diagram (ERD):** It defines the database structure of the system, identifying the entities such as users, delivery records, routes, and reports, along with their 

_STI College_ _**–** General Santos, Inc._ 

19 

relationships. The ERD ensures efficient database organization, data consistency, and secure storage of logistics and delivery information within the system. 

## **Technical Background** 

Technologies To Be Used 

The technological foundation of the Web-Based Business-to-Business (B2B) Crop Distribution and Logistics Management System with Real-Time Tracking is carefully selected to support a modern, efficient, and scalable web-based application. The system architecture  consists  of  both  front-end  and  back-end  technologies  chosen  for  their performance, flexibility, and ability to provide a user-friendly experience. This section presents an overview of the core technologies used in the development of the system. 

For the front-end, the system utilizes Tailwind CSS, a utility-first CSS framework that allows the development of clean, responsive, and visually consistent user interfaces. Tailwind CSS enables faster UI design by providing ready-to-use styling classes, which helps maintain consistency across different pages of the system. This ensures that users such as administrators, logistics coordinators, farmers, and drivers can easily navigate and interact with the platform. 

To improve mobile accessibility for field operations, Progressive Web Application (PWA) technology will also be used to make the web system installable and mobilefriendly for drivers. It allows the system to run through supported browsers with app-like access,  home-screen  installation,  and  improved  usability  on  mobile  devices  while preserving the web-based architecture of the platform. 

For the back-end, the system uses Laravel, a PHP-based web framework known for its structured architecture, security features, and efficient handling of server-side processes. Laravel is responsible for managing system logic, handling user requests, processing data, and ensuring secure communication between the front-end and the database. It also 

_STI College_ _**–** General Santos, Inc._ 

20 

supports  the  implementation  of  role-based  access  control  to  manage  different  user permissions within the system. 

The system uses MySQL as its database management system. MySQL is a reliable and widely used relational database that stores important system data such as user accounts, pickup locations, delivery records, and reports. It ensures data integrity, efficient retrieval, and secure storage of logistics information necessary for the system’s operations. 

To support the development and testing environment, XAMPP is used as a local server solution. XAMPP provides an integrated environment that includes Apache, PHP, and MySQL, allowing the proponents to run and test the system locally before deployment. This helps streamline development and ensures that the system functions properly in a controlled environment. 

For coding and development, Visual Studio Code (VS Code) is used as the primary Integrated Development Environment (IDE). VS Code offers features such as code editing, debugging  tools,  and  extensions  that  improve  development  efficiency  and  code management. It allows the team to organize, develop, and maintain the system effectively throughout the project lifecycle. 

By combining Laravel, MySQL, Tailwind CSS, Progressive Web Application (PWA) technology, XAMPP, and Visual Studio Code, the system is designed to be reliable, scalable, and easy to maintain. These technologies work together to deliver a functional and user-friendly platform that improves coordination, monitoring, and efficiency in agricultural logistics operations in General Santos City and Polomolok. 

## **Calendar of Activities** 

The listed activities below are the proponent’s both completed and ongoing activities during the completion of the project proposal: 

The  capstone  project  titled  “Web-Based  Business-to-Business  (B2B)  Crop 

_STI College_ _**–** General Santos, Inc._ 

21 

Distribution and Logistics Management System with Real-Time Tracking” officially commenced in February. The project began with the formation of the group during the second week, followed by the presentation of partial and candidate titles, and the selection of the official project title, all of which spanned through February and into the early weeks of March. During the last week of February, the team also completed the selection of a Capstone Project Adviser, choosing Sir Ivan Carl H. Delizo as their Adviser. In March, the group initiated the process of visiting sites and collecting data. Work on this project continued  throughout  the  month  and  into  April.  Chapter  I:  Introduction  has  been completed. Continue with Chapter II: Review of Related Literature and Systems, and Chapter III: The Technical Background is currently under development, suggesting that there will be ongoing research and content development activities in March and April. The group has been concurrently engaged in the planning and design phase which encompasses the formulation of critical system diagrams, including the Context Flow Diagram (CFD), Data Flow Diagram (DFD), Entity Relationship Diagram (ERD), and additional technical illustrations requisite for system development. The planning phase persisted concurrently with the ongoing system development, which advanced steadily through March and April. By mid-April, the team is preparing for Capstone 1 final defense, scheduled on April 27, expecting major revisions for the documents. 

## **Resources** 

The following hardware and software resources will be used in the development, testing, and operation of the proposed system. 

Hardware: 

Laptop: 

|Processor|Intel Core i5|
|---|---|
|Operating System|Windows 10|
|Ram|8.0 GB|
|SSD|256 GB|



_STI College_ _**–** General Santos, Inc._ 

22 

Smart Phone: 

|Processor|Quad-core|
|---|---|
|Operating System|Android 10|
|RAM|4 GB|
|Storage|64 GB|



Software: 

**Integrated Development Environment (IDE)** - The proponents used Visual Studio Code (VS Code) as the primary Integrated Development Environment (IDE) for coding, debugging, and managing the project’s source code. VS Code provides essential features such as syntax highlighting, intelligent code completion, built-in Git integration, and extensive support for web development languages. These features help improve development efficiency and allow the team to build and maintain the Web-Based Businessto-Business (B2B) Crop Distribution and Logistics Management System with Real-Time Tracking effectively. 

**Database Management System** – MySQL is used as the database management system for storing and managing system data. It handles important information such as user accounts, pickup locations, delivery records, and generated reports. This supports core system functions including user authentication, data storage, and retrieval. Using SQL commands for inserting, updating, and retrieving data helps ensure data accuracy, security, and  consistency.  MySQL’s  compatibility  with  various  programming  languages  and frameworks makes it a reliable choice for back-end development. 

**Web  Testing Tool** - To support  local testing and system  validation before deployment, the proponents used XAMPP, an open-source and cross-platform web server solution. XAMPP includes Apache, MySQL, and PHP, allowing developers to simulate a real server environment on a local machine. The XAMPP Control Panel enables easy 

_STI College_ _**–** General Santos, Inc._ 

23 

management of services, ensuring that the system operates correctly during development and testing phases before it is deployed online. 

**Web Framework** - The system utilizes Laravel, a PHP-based web framework, for handling  server-side  logic  and  application  structure.  Laravel  provides  a  clean  and organized  coding  framework,  built-in  security  features,  and  tools  for  efficient  data processing. It supports the development of dynamic web applications and ensures that system processes such as user management, logistics coordination, and data handling are performed securely and efficiently for administrators, logistics coordinators, farmers, and drivers. 

**Front-End Framework** - Tailwind CSS is used as the front-end styling framework for designing the system’s user interface. It provides utility-based classes that allow developers to create responsive and visually consistent layouts. Tailwind CSS helps improve the overall user experience by making the interface clean, accessible, and easy to navigate for administrators, logistics coordinators, farmers, and drivers. 

**Progressive Web Application (PWA)** – To enhance mobile accessibility for drivers, the system incorporates Progressive Web Application (PWA) technology. PWA enables the web-based system to be installed on supported mobile devices, providing applike access through a web browser. It supports features such as home-screen installation and improved mobile usability while maintaining the web-based architecture of the system. This allows drivers to conveniently access system functions in the field without requiring a fully native mobile application. 

**Microsoft Word –** is a word processing tool used for designed for creating, editing, formatting, and printing various types of documents. 

## **Requirements Analysis** 

The Web-Based Business-to-Business (B2B) Crop Distribution and Logistics Management System with Real-Time Tracking was conceptualized in response to the 

_STI College_ _**–** General Santos, Inc._ 

24 

common problems encountered in crop transportation and logistics operations in General Santos City and Polomolok. Based on the project context, current logistics practices often rely  on  manual  coordination  through  phone  calls,  text  messages,  and  face-to-face communication, which may result in delays, poor coordination, underutilized vehicles, and limited  visibility  during  delivery  operations.  These  issues  affect  the  efficiency  and reliability of transporting crops, fruits, and vegetables from pickup points to drop-off locations. 

To address these problems, the system requirements were analyzed according to the actual operational needs of the intended users, namely the Administrator, Logistics Coordinator, Driver, and Farmer. The analysis showed that a web-based logistics platform is needed to improve communication and organize logistics activities in a more structured manner. To support field operations, particularly for drivers, the system may also be deployable as a Progressive Web Application (PWA), allowing mobile-friendly and installable access through supported web browsers while maintaining its web-based architecture. Since manual methods make it difficult to monitor deliveries and respond to delays, the system must include a real-time tracking feature that provides visibility into vehicle movement and delivery progress. In addition, because less efficient trip planning may lead to repeated travel and poor truck utilization, the system must support route planning along selected delivery paths. 

The analysis also indicated the need for a mapping feature that allows pickup points and drop-off locations to be visualized geographically. This is necessary to help users plan delivery routes more efficiently and identify pickup points that may be served within a single  trip.  Likewise,  because  logistics  activities  need  proper  documentation  and monitoring, the system must also be capable of storing delivery records and generating basic  reports  related  to  route  history,  delivery  status,  and  vehicle  activity.  These requirements were identified to ensure that the proposed system directly responds to the logistical challenges presented in the study. 

Furthermore, the analysis considered the technical and operational limitations of 

_STI College_ _**–** General Santos, Inc._ 

25 

the project. The system is intended only for logistics coordination and monitoring within General Santos City and Polomolok, is limited to the transport of crops, fruits, and vegetables. It does not include crop trading, payment processing, or advanced technologies such as AI-based route optimization, IoT sensors, and predictive analytics. By defining these boundaries, the requirements analysis ensures that the proposed system remains realistic, focused, and aligned with the actual objectives and scope of the study. 

## **Requirements Documentation** 

The identified requirements for the Web-Based Business-to-Business (B2B) Crop Distribution  and  Logistics  Management  System  with  Real-Time  Tracking  were documented to serve as the basis for the design and development of the proposed system. These requirements describe the specific functions, user access, and system behavior necessary to support crop distribution and logistics coordination in General Santos City. The documentation ensures that the features to be developed are clear, organized, and aligned with the needs identified during requirements analysis. 

The  system  shall  provide  user  account  management  for  authorized  users, specifically the Administrator, Logistics Coordinator, Driver, and Farmer. It shall allow the registration, updating, and management of user information while ensuring that only authorized individuals can access the platform according to their assigned roles. The system shall also allow the highlighting of pickup points and drop-off locations, including their basic details and geographic coordinates, so that these locations may be displayed on a digital map for visualization and planning purposes. 

The system shall include a route planning feature that enables authorized users to create delivery routes. This feature shall support more efficient pickup scheduling and better truck utilization by allowing pickup points to be served within a single trip whenever possible. In addition, the system shall provide a real-time delivery monitoring feature that allows authorized users to view delivery progress and vehicle movement through a web browser. This requirement is intended to improve visibility and coordination during 

_STI College_ _**–** General Santos, Inc._ 

26 

## transport operations. 

The system shall also store digital records of logistics activities, including pickup and drop-off information, route history, timestamps, and delivery status. It shall generate basic reports such as delivery summaries, route utilization reports, and vehicle activity reports to support monitoring, evaluation, and documentation. The interface shall be userfriendly, accessible through standard web browsers, and suitable for the operational needs of registered system users in General Santos City. For field operations, selected driver-side functions may also be deployable as a Progressive Web Application (PWA), allowing mobile-friendly and installable access through supported web browsers while maintaining the web-based nature of the system. 

In addition, the system shall observe basic non-functional requirements necessary for effective implementation. It shall maintain data accuracy based on user input, support secure user authentication, and provide an organized and easy-to-use interface for different user types. However, the system shall remain limited to logistics coordination and monitoring  only.  It  shall  not  include  payment  processing,  crop  trading,  advanced forecasting, automated farm detection, or intelligent route optimization technologies. These documented requirements define the intended behavior and limitations of the proposed system and serve as a guide throughout the development process. 

## **Design of Software, System, Product, and/or Processes** 

In this phase, the Web-Based Business-to-Business (B2B) Crop Distribution and Logistics Management System with Real-Time Tracking is designed to provide a secure, scalable, and effective digital platform for managing agricultural logistics in General Santos City and Polomolok. The system connects cooperative farmers, independent farmers, logistics coordinators, drivers, and administrators through a centralized web-based platform that improves communication, coordination, and monitoring of crop distribution activities. It includes key modules such as User Management for secure registration, login, and role-based access; Crops Management for uploading and updating crop listings and 

_STI College_ _**–** General Santos, Inc._ 

27 

availability; Delivery Request and Coordination for handling crop requests, and responses; Messaging/Chat for direct communication between farmers and logistics coordinators regarding inquiries, delivery updates, and transaction details; and Logistics and Delivery for managing pickup scheduling, assigning drivers, and providing real-time delivery status tracking. The system also features Activity Logs and System Monitoring, allowing administrators to oversee transactions, generate reports, and monitor overall system performance. Integrated databases securely store user records, crop information, order data, messages, and system activities. Overall, the design aims to improve operational efficiency, reduce delays caused by manual processes, enhance transparency in logistics operations, and provide a reliable digital solution for better crop distribution and agricultural business management. 

Technology Stack 

To ensure smooth functionality, secure data, and interactive features, the system utilizes the following technologies: 

## 1. Front-end Development: 

Blade Templating Engine (for creating dynamic, responsive, and interactive user interfaces with reusable components within the Laravel ecosystem), Tailwind CSS (a utility-first CSS framework for rapid and customizable styling to ensure a clean and visually consistent interface), and Progressive Web Application (PWA) support (to provide mobile-friendly, installable, and app-like browser access for drivers while preserving the web-based nature of the system). 

## 2. Back-end Development: 

Laravel Framework: An effective PHP framework used for server-side logic, managing system requests, and ensuring secure data handling. 

## 3. Database Management: 

_STI College_ _**–** General Santos, Inc._ 

28 

MySQL: A reliable relational database used for structured storage and retrieval of user accounts, locations, and delivery records. 

4. Development Environment: 

XAMPP (for local testing and simulating a real server environment) and Visual Studio Code (the primary IDE for coding, debugging, and managing the project's source code). 

5. Deployment: 

Vercel: Utilized for reliable web hosting and live server management to ensure the platform is accessible via standard web browsers. 

User Interface Design Standards 

- Responsiveness: The system automatically adapts its interface for optimal viewing across various devices, including desktops, tablets, and smartphones, ensuring accessibility for users in different locations. To further support field operations, selected driver-side functions may also be made available through a Progressive Web Application (PWA), providing installable and mobile-friendly access on supported devices while maintaining the web-based nature of the system. 

- User-Friendly  Elements:  Interactive  Logistics  Dashboards  featuring  digital mapping interfaces to visually display pickup and drop-off locations and real-time vehicle movement, Simplified Navigation through clear and intuitive interfaces designed specifically for users with limited technical skills to ensure ease of operation, and Route Visualization tools that identify multiple farms along a single delivery path to maximize truck utilization and efficiency. 

Security Measures 

_STI College_ _**–** General Santos, Inc._ 

29 

- Data Protection: All sensitive logistics data, including personal contact information and delivery history, are stored securely to maintain data integrity. 

- User  Authentication  &  Role-Based  Access  Control:  The  system  implements controlled access where the Admin, Drivers, and Logistics Coordinator have different permissions. Admins manage accounts, while drivers and Coordinator access tracking and coordination features. 

- System Hardening: Implementation of secure coding practices through the Laravel framework to protect against common vulnerabilities such as SQL injection and unauthorized access. 

## Processes and Workflow 

The operational architecture of the system is structured into modular functional blocks  that  manage  the  lifecycle  of  agricultural  logistics  tracking from  initial  user registration to final delivery fulfillment. The systemic movement of data across these processes is visually mapped via the Context Flow Diagram (CFD) and Data Flow Diagram (DFD) located in Appendix C (see page 42). 

- User  Registration  &  Management:  All  core  system  actors,  Administrators, Logistics Coordinators, Drivers, and Farmers—initialize accounts or registration profiles through the web platform. External actors (Farmers and Logistics Partners) submit their profiles via a public onboarding interface and remain in a pending state until their identity credentials, farm locations, or business permits are manually approved. The Administrator retains high-level privileges to modify user details, verify these business credentials, and deactivate or archive unauthorized profiles to preserve platform security. 

- Crops Management: Registered farmers input their post-harvest crop availability data, specifying agricultural product categories (e.g., crops, fruits, vegetables) and volume  metrics  into  the  platform.  The  system  processes  this  data  against 

_STI College_ _**–** General Santos, Inc._ 

30 

verification  parameters  managed  by  the  Administrator,  updating  the  Crops Database to create a reliable, verified directory of available hauls for logistics planning. 

- Delivery Request and Coordination: Once a B2B commercial transaction is prearranged externally, the farmer logs a formal hauling request containing critical physical payload attributes (total weight, box/crate counts, and designated drop-off coordinates). The system commits these parameters to the Orders Process Database, transitioning the request into a active fulfillment state visible to authorized logistics personnel. 

- Messaging / Chat Module: To centralize operational coordination and replace fragmented  third-party  communication  channels,  an  embedded  messaging infrastructure facilitates real-time text communication. Farmers and Logistics Coordinators use this process to negotiate logistical cost estimations, adjust pickup schedules, and clarify loading instructions. To prevent scope creep, this module handles logistics coordination only and completely excludes payment processing gateways or financial transaction logic. 

- All Processes (System Audit Logging): Operating as a background compliance layer, this process intercepts operational metadata from all active modules (account modifications, harvest listings, status changes, and message initializations). It formats and stores these events as immutable entries within the Activity Logs database, allowing the Administrator to review system audits and compile historical operational data. 

- Logistics and Delivery Management: This core execution module manages the distribution tracking. Logistics Coordinators use a deterministic proximity-sorting dashboard to sequence multiple farm pickup nodes along a travel track, verifying that cumulative haul volumes do not breach the maximum capacity limits of the assigned vehicle profile. Drivers interact with this process via a mobile-friendly 

_STI College_ _**–** General Santos, Inc._ 

31 

Progressive Web Application (PWA), streaming client-side GPS coordinates to the system and submitting manual status updates (e.g., Assigned, Loading, In-Transit, Fulfilled) to provide real-time tracking visibility to stakeholders. 

_STI College_ _**–** General Santos, Inc._ 

32 

## **REFERENCES** 

Accorsi, R., Bortolini, M., & Ferrari, E. (2022). Sustainable food supply chain management: A systematic literature review. Sustainability, 14(3), 1858. https://doi.org/10.3390/su14031858 

Aungkulanon, P., Atthirawong, W., Luangpaiboon, P., & Chanpuypetch, W. (2024). Navigating supply chain resilience: A hybrid approach to agri-food supplier selection. _Mathematics, 12_ (10), 1598. https://doi.org/10.3390/math12101598 

Babar, A. Z., & Akan, O. B. (2024). Sustavanable and precision agriculture with the Internet of Everything (IoE). _arXiv_ . https://arxiv.org/abs/2404.06341 

Bafe. (2023). Philippine food chain logistics masterplan 2023–2033. Philippine Agricultural and Fisheries Economics and Policy Research Institute. https://bafe.gov.ph/wpc ontent/uploads/2025/03/20230802_PFCLM23-33_Final-1-1-1.pdf 

Ben-Daya, M., Hassini, E., & Bahroun, Z. (2021). Internet of Things and supply chain management: A literature review. _International Journal of Production Research, 59_ (15), 4719–4742. https://www.tandfonline.com/doi/full/10.1080/00207543.2017.1402140 

Bigliardi, B., Filippelli, S., Petroni, A., & Tagliente, L. (2022). _The digitalization of supply chain: A review_ . _Procedia Computer Science, 200_ , 1806–1815. https://doi.org/10.1016/j.procs.2022.01.381 

Bimp-Eaga. (2022). General Santos City Green City Action Plan. https://bimp- 

eaga.asia/documents-and-publications/green-city-action-plan-general-santos-city 

Bosona, T., & Gebresenbet, G. (2023). The role of blockchain technology in promoting 

traceability systems in agri-food production and supply chains. _Sensors, 23_ (11), 5342. https://doi.org/10.3390/s23115342 

_STI College_ _**–** General Santos, Inc._ 

33 

Cahyadi, E. R., Hidayati, N., Zahra, N., & Arif, C. (2024). Integrating circular economy principles into agri-food supply chain management: A systematic literature review. _Sustainability, 16_ (16), 7165. https://doi.org/10.3390/su16167165 

Carasco, M. L., Clemente, M. N. B., & Cruz, C. P. O. (2026). _Harvest: A mobile platform for direct farmer to buyer transactions_ . International Journal of Latest Technology in Engineering Management & Applied Science, 15(3), 927–931. 

https://doi.org/10.51583/IJLTEMAS.2026.150300079 

Chen, T., Lv, L., Wang, D., Zhang, J., Yang, Y., Zhao, Z., et al. (2023). Empowering agrifood system with artificial intelligence: A survey of the progress, challenges and opportunities. _ArXiv_ . https://arxiv.org/abs/2305.01899 

Chen, W., Chen, H., Yin, J., & Sun, W. (2025). Evaluation of agricultural product e-commerce logistics service capability using entropy weight–TOPSIS method. _PLOS ONE, 20_ (5). https://doi.org/10.1371/journal.pone.0325532 

Chong, M. A., Cordova, M. L. D., Quintana, A. A. M., & Camaro, P. J. C. (2022). The impact of logistics on marketing margin in the Philippine agricultural sector. _Journal of Economics, Finance and Accounting Studies, 3_ (2), 300–317. 

https://doi.org/10.32996/jefas.2021.3.2.27 

Cortez-Clavo, L. K., Salazar-Muñoz, M. I., & Morán-Santamaría, R. O. (2025). Digitalisation to improve automated agro-export logistics: A comprehensive bibliometric analysis. _Sustainability, 17_ (10), 4470. https://doi.org/10.3390/su17104470 

Granillo-Macías, R., González-Hernández, I., & Olivares-Benítez, E. (2023). Logistics 

technologies  in  agri-food  supply  chains:  A  review  of  digital  transformation. _International Journal of Logistics Research and Applications_ . 

https://doi.org/10.1080/13675567.2023.2184467 

_STI College_ _**–** General Santos, Inc._ 

34 

- Iftikhar, A., Ali, I., Arslan, A., & Tarba, S. (2022). Digital innovation, data analytics, and supply chain resiliency: A bibliometric-based systematic literature review. _Annals of Operations Research_ . https://doi.org/10.1007/s10479-022-04765-6 

- Ivanov, D. (2022). Viable supply chain model: Integrating agility, resilience, and sustainability perspectives—Lessons from and thinking beyond the COVID-19 pandemic. _Annals of Operations Research, 319_ , 1411–1431. https://link.springer.com/article/10.1007/s10479020-03640-6 

- Krstić, M., Agnusdei, G. P., Tadić, S., & Miglietta, P. P. (2023). Prioritization of e-traceability drivers in the agri-food supply chains. _Agricultural and Food Economics, 11_ (1), 42. https://link.springer.com/article/10.1186/s40100-023-00284-5 

- Le, T. V., & Fan, R. (2023). Digital twins for logistics and supply chain systems: Literature review,conceptual framework, research potential, and practical challenges. _ArXiv_ . https://arxiv.org/abs/2311.17317 

- Lei, D., Lin, H., & Tai, Y. (2023). Research on innovation of agricultural product logistics circulation system under the background of big data. _Engineering Proceedings, 38_ (1), 54. https://www.mdpi.com/2673-4591/38/1/54 

- Lin, K., Ishihara, H., Tsai, C., Hung, S., & Mizoguchi, M. (2022). Shared logistic service for resilient  agri-food  systems. _Sustainability,  14_ (3),  1858.  https://www.mdpi.com/20711050/14/3/1858 

- Lubag, M., Bonifacio, J., Tan, J. M., Concepcion II, R., Mababangloob, G. R., Galang, J. G., & Maniquiz-Redillas, M. (2023). Technology-intensified agricultural supply chains and quality of life. _Sustainability, 15_ (17), 12809. https://www.mdpi.com/20711050/15/17/12809 

- Prajapati, D., Zhou, F., Dwivedi, A., Singh, T., Lakshay, L., & Pratap, S. (2022). Sustainable agrofood supply chain in e-commerce. _Sustainability, 14_ (14), 8698. https://www.mdpi.com/2071-1050/14/14/8698 

_STI College_ _**–** General Santos, Inc._ 

35 

Queiroz, M. M., & Wamba, S. F. (2021) **.** Digital transformation and data-driven supply chains: A framework for research and practice. https://pmc.ncbi.nlm.nih.gov/articles/PMC8243624/ 

- Kouhizadeh, M., Saberi, S., & Sarkis, J. (2021). Blockchain technology and sustainable supply chains. _International Journal of Production Economics, 231_ , 107831. https://doi.org/10.1016/j.ijpe.2020.107831 

- Singh, R., Bhatia, M., & Kumar, A. (2021). IoT research in supply chain management. _Materials Today: Proceedings_ . https://doi.org/10.1016/j.matpr.2021.08.272 

- Tubis, A. A., Grzybowska, K., & Król, B. (2023). Supply chain in the digital age. _Sustainability, 15_ (14), 11391. https://www.mdpi.com/2071-1050/15/14/11391 

- Wang, G., Li, S., Yi, Y., Wang, Y., & Shin, C. (2024). Digital technology in agro-food supply chains. _Agriculture, 14_ (6), 900. https://doi.org/10.3390/agriculture14060900 

- Yang, L. (2023). Agricultural product logistics information system. In _Proceedings of the International Conference on E-Commerce and Internet Technology_ (pp. 119–125). 

- https://doi.org/10.2991/978-94-6463-210-1_15 

- Zhang, G., Chai, J., & Xie, J. (2025). Digital transformation and agricultural transportation resilience. _Frontiers in Sustainable Food Systems_ . https://doi.org/10.3389/fsufs.2025.1564443 

- Zrelli, I., & Rejeb, A. (2024). IoT applications in logistics and supply chains. _Heliyon, 10_ (16), e36578. https://doi.org/10.1016/j.heliyon.2024.e36578 

_STI College_ _**–** General Santos, Inc._ 

36 

## **APPENDICES** 

_STI College_ _**–** General Santos, Inc._ 

37 

_STI College_ _**–** General Santos, Inc._ 38 

## **APPENDIX A. LIST OF TABLES** 

_STI College_ _**–** General Santos, Inc._ 

39 

## **Table 1 Software tools** 

|Software|Specification|Description|
|---|---|---|
|Operating System|Windows 10|An<br>Operating<br>system<br>designed  to  offer  a  more<br>modern user experience and<br>introduces new tools, better<br>capabilities, and tighter<br>integration|
|Programming<br>Language|Java, PHP|Java structured the interface<br>for a responsive layout, and<br>PHP managed backend<br>processes for smooth system<br>operation|
|Integrated<br>Development<br>Environment (IDE)|Visual Studio Code, Android Studio|Visual Studio Code and<br>Android Studio are powerful<br>development tools that allow<br>users to write, edit, debug,<br>and build code in one place.<br>VS Code is lightweight and<br>flexible<br>for<br>general<br>programming,<br>while<br>Android Studio  is designed<br>specifically  for  developing<br>Android applications.|
|Database Software|PHP MyAdmin, XAMPP|PHP  MyAdmin  is  a  web-<br>based  tool  that  provides  a<br>user-friendly  interface  for<br>managing MySQL database.<br>XAMPP is an all-in-one<br>package that includes<br>Apache,  MySQL,  and  PHP<br>interpreters. It facilitated<br>efficient simulation of a live<br>server<br>environment,<br>enabling the team to test and<br>debug the system before<br>deployment.|
|Documentation Tool|Microsoft Word and Google Docs|Word processing software for<br>creating<br>project<br>documentation, user manual<br>and reports.|
|Presentation Tool|Microsoft PowerPoint|Presentation software for|



_STI College_ _**–** General Santos, Inc._ 

40 

creating visual presentations. 

Table  1  presents  the  software  tools  used  in  the  development,  documentation,  and presentation of the system. 

**Table 2. Hardware Specifications** 

|**Table 2.**<br>**Hardware Specifications**||
|---|---|
|<br>Processor|Intel Core i5 10thGen|
|Display|1440p|
|Memory|16gb DDR4|
|Storage|512gb Nvme m.2|
|Internet Connection|Internet Broadband/ Wifi|



Table 2 shows the hardware specifications required to support the system development and testing process. 

_STI College_ _**–** General Santos, Inc._ 

41 

## **APPENDIX B. FIGURES** 

_STI College_ _**–** General Santos, Inc._ 

42 

## **Figure 2: Context Flow Diagram** 

_STI College_ _**–** General Santos, Inc._ 

43 

**Figure 2.1 Data Flow Diagram** 

**Figure 2.2 Entity Relationship Diagram** 

_STI College_ _**–** General Santos, Inc._ 

44 

_STI College_ _**–** General Santos, Inc._ 

45 

## **APPENDIX C. CALENDAR OF ACTIVITIES** 

_STI College_ _**–** General Santos, Inc._ 

46 

## **Figure 3: Gantt Chart** 

_STI College_ _**–** General Santos, Inc_ 47 

**(Blue) - Completed Activities** 

**(Yellow) - On going** 

Figure 2 presents the Gantt Chart of Activities, showing the timeline and sequence of tasks in the development of the Web-Based B2B Crop Distribution and Logistics Management System in General Santos City. The chart highlights the progression of activities from group formation, project planning, data gathering, and system design to development, testing, and final defense. Tasks marked in blue indicate completed activities, while those in yellow represent ongoing tasks. This structured timeline demonstrates an organized workflow and allows efficient tracking of project progress 

_STI College_ _**–** General Santos, Inc_ 48 

## **APPENDIX D. EVALUATION FORMS AND TOOLS** 

_STI College_ _**–** General Santos, Inc_ 

49 

## Figure 4: Survey Questionnaire for Logistics Coordinators 

_STI College_ _**–** General Santos, Inc_ 

50 

## Figure 5: Survey Questionnaire for Farmers 

_STI College_ _**–** General Santos, Inc_ 

51 

## Figure 6: Survey Questionnaire for Drivers 

_STI College_ _**–** General Santos, Inc_ 

52 

## _Figure 7:_ Research Correspondent Form 

_STI College_ _**–** General Santos, Inc_ 

53 

_STI College_ _**–** General Santos, Inc_ 

54 

## **APPENDIX A. RESOURCE PERSONS** 

_STI College_ _**–** General Santos, Inc_ 

55 

The following person have made a significant contribution to the development and success of the proponent’s capstone project: 

**Mr. Ivan Carl H. Delizo** - The project adviser of the proponents who rendered his expertise and insights for the development of this web-based system. 

**Ma’am Jolina M. Migriño** - The capstone coordinator who provided administrative guidance and monitored the project’s compliance with academic requirements. 

**Sir Angelbert M. Sayod** - The Bookkeeper of City Food Terminal Multi-purpose Cooperative who granted permission and provide for the conduct of surveys and interviews within the coop. 

**Ma’am Mylene C. Carmona** - The Secretary of Tinagacan Agrarian Reform Beneficiaries Cooperative who granted permission and provide for the conduct of surveys and interviews within the coop 

**Farmers -** The primary beneficiaries and resource persons of the study who shared valuable information regarding farming practices, crop distribution challenges, transportation methods, and the current process of delivering agricultural products within their respective areas. 

**Drivers -** The resource persons responsible for transporting agricultural products who provided insights about delivery operations, transportation challenges, route conditions, and logistics coordination during the conduct of the study. 

_STI College_ _**–** General Santos, Inc_ 

56 

## **APPENDIX E. PROOF OF DATA GATHERING** 

_STI College_ _**–** General Santos, Inc_ 

57 

The proponents conducted data-gathering activities through interviews and observations with Dave Ayop, Mylene C. Carmona, farmers, and logistics drivers involved in agricultural transportation and distribution operations. The sessions focused on understanding current practices in crop transportation coordination, delivery scheduling, communication methods, route management, and monitoring of delivery activities. Participants shared common challenges such as transportation delays, unclear delivery instructions, limited coordination, lack of real-time updates, traffic conditions, and difficulties in managing pickup and drop-off locations. The proponents also gathered insights regarding the possible use of features such as real-time tracking, interactive maps, delivery status updates, route visualization, in-app messaging, and mobile-friendly accessibility through a Progressive Web Application (PWA). The information collected through these interviews and observations served as the foundation for identifying user requirements and developing the proposed Web-Based Business-to-Business (B2B) Crop Distribution and Logistics Management System with Real-Time Tracking aligned with the operational needs of agricultural stakeholders. 

_STI College_ _**–** General Santos, Inc_ 

58 

## **APPENDIX F. TRANSCRIPT OF INTERVIEW** 

_STI College_ _**–** General Santos, Inc_ 

59 

## **Transcript of Interview for Logistics Coordinator** 

## I. Informant Profile 

Name (Optional)/Alias: Mylene C. Carmona 

Position: Secretary 

Gender: Female 

## II. Cooperative Profile 

1. How many member-farmers are registered in the cooperative? 

The cooperative has 404 registered members as of December 2025, all of whom are farmers. 

2. How many vehicles for delivery are currently present in the management? 

The cooperative operates a total of four (4) delivery vehicles, consisting of: 

   - One (1) hauler truck 

   - Two (2) elf trucks 

   - One (1) additional truck 

3. What are the agricultural products handled? 

The cooperative handles various agricultural products; however, a complete list was not specified during the interview. 

4. What are the routes of service on delivery? 

Delivery operations are conducted on a citywide basis, covering multiple service areas. 

## III. Logistics Management 

5. What logistics or distribution management system is currently in place? 

The cooperative currently uses a manual logistics management system, with no integrated digital platform. 

6. How are transportation tasks such as scheduling, pooling requests, and route assignments currently managed? 

Transportation tasks are managed through mobile communication, particularly via chat and phone calls, without a formal automated system. 

7. What are the challenges faced by the logistics/operations department? 

The cooperative faces several challenges, including: 

- High fuel costs 

- Trucking-related issues 

- Scheduling inefficiencies 

- Lack of real-time tracking 

_STI College_ _**–** General Santos, Inc_ 

60 

8. How are deliveries recorded and managed? 

Deliveries are recorded using manual documentation methods, which may lead to inefficiencies. 

9. How are these challenges currently being addressed by the management? These challenges are addressed through continuous communication and coordination, although no structured digital system is currently implemented. 

## IV. Logistics Needs and Goals 

10. What are the primary goals for improving your distribution efficiency in the succeeding year? 

The cooperative aims to increase purchases and shipment volume to improve distribution efficiency. 

11. What specific logistics processes need improvement? 

Processes that require improvement include: 

- Coordination and communication 

- Delivery scheduling 

- Monitoring and tracking deliveries 

12. What are the key performance indicators (KPIs) for successful delivery operations? The key indicators include: 

   - Efficient and smooth delivery process 

   - Reduced workload for drivers 

   - Organized scheduling system 

13. What are the company’s priorities in terms of driver coordination and fleet maintenance? 

The cooperative prioritizes: 

- Basic vehicle maintenance (e.g., oil checks) 

- Driver responsibility for vehicle condition 

- Ensuring readiness of vehicles before operations 

## V. Digital Tools 

14. Based on the current challenges faced by the management, would you like to have a website that will efficiently and effectively perform these tasks? 

☑ Yes 

15. If yes, what expectations would you like to see using the website? The cooperative expects the website to: 

_STI College_ _**–** General Santos, Inc_ 

61 

   - Provide real-time monitoring and tracking (GPS-based) 

   - Allow tracking of delivery vehicle locations 

   - Improve coordination and communication 

   - Reduce manual workload and operational delays 

16. Using the website, how will it help your business in the market? The website is expected to: 

   - Improve operational efficiency 

   - Reduce time and effort in monitoring 

   - Enhance delivery processes 

   - Support overall business growth 

_STI College_ _**–** General Santos, Inc_ 

62 

## **Transcript of Interview of Farmer** 

_STI College_ _**–** General Santos, Inc_ 

63 

_STI College_ _**–** General Santos, Inc_ 

64 

_STI College_ _**–** General Santos, Inc_ 

65 

## **Transcript of Interview of Driver** 

_STI College_ _**–** General Santos, Inc_ 

66 

_STI College_ _**–** General Santos, Inc_ 

67 

## **APPENDIX G. REVISION LIST** 

_STI College_ _**–** General Santos, Inc_ 

68 

_STI College_ _**–** General Santos, Inc_ 

69 

_STI College_ _**–** General Santos, Inc_ 

70 

_STI College_ _**–** General Santos, Inc_ 

71 

## **APPENDIX H. ADVISER ACCEPTANCE FORM** 

_STI College_ _**–** General Santos, Inc_ 

72 

_STI College_ _**–** General Santos, Inc_ 

73 

## **APPENDIX I. ACCOMPLISHMENT AND CONSULTATION FORMS** 

_STI College_ _**–** General Santos, Inc_ 

74 

_STI College_ _**–** General Santos, Inc_ 

75 

_STI College_ _**–** General Santos, Inc_ 

76 

_STI College_ _**–** General Santos, Inc_ 

77 

_STI College_ _**–** General Santos, Inc_ 

78 

_STI College_ _**–** General Santos, Inc_ 

79 

_STI College_ _**–** General Santos, Inc_ 

80 

_STI College_ _**–** General Santos, Inc_ 

81 

_STI College_ _**–** General Santos, Inc_ 

82 

## **APPENDIX E. LETTER** 

_STI College_ _**–** General Santos, Inc_ 

83 

_STI College_ _**–** General Santos, Inc_ 

84 

_STI College_ _**–** General Santos, Inc_ 

85 

_STI College_ _**–** General Santos, Inc_ 

86 

_STI College_ _**–** General Santos, Inc_ 

87 

## **APPENDIX F. QUESTIONNAIRES** 

_STI College_ _**–** General Santos, Inc_ 

88 

## **QUESTIONNAIRE FOR LOGISTICS COORDINATOR** 

Tinagacan Agrarian Reform Beneficiaries Cooperative (TARBC) 

## I. Informant Profile 

Name (Optional)/Alias: Not specified 

Position: Cooperative Representative (Manager/Supervisor) 

Gender: Not specified 

## II. Cooperative Profile 

1. How many member-farmers are registered in the cooperative? 

The cooperative has 404 registered members as of December 2025, all of whom are farmers. 

2. How many vehicles for delivery are currently present in the management? 

The cooperative operates a total of four (4) delivery vehicles, consisting of: 

   - One (1) hauler truck 

   - Two (2) elf trucks 

   - One (1) additional truck 

3. What are the agricultural products handled? 

The cooperative handles various agricultural products; however, a complete list was not specified during the interview. 

4. What are the routes of service on delivery? 

Delivery operations are conducted on a citywide basis, covering multiple service areas. 

## III. Logistics Management 

## 5. What logistics or distribution management system is currently in place? 

The cooperative currently uses a manual logistics management system, with no integrated digital platform. 

6. How are transportation tasks such as scheduling, pooling requests, and route assignments currently managed? 

Transportation tasks are managed through mobile communication, particularly via chat and phone calls, without a formal automated system. 

7. What are the challenges faced by the logistics/operations department? 

The cooperative faces several challenges, including: 

- High fuel costs 

- Trucking-related issues 

- Scheduling inefficiencies 

- Lack of real-time tracking 

_STI College_ _**–** General Santos, Inc_ 

89 

8. How are deliveries recorded and managed? 

Deliveries are recorded using manual documentation methods, which may lead to inefficiencies. 

9. How are these challenges currently being addressed by the management? These challenges are addressed through continuous communication and coordination, although no structured digital system is currently implemented. 

## IV. Logistics Needs and Goals 

10. What are the primary goals for improving your distribution efficiency in the succeeding year? 

The cooperative aims to increase purchases and shipment volume to improve distribution efficiency. 

11. What specific logistics processes need improvement? 

Processes that require improvement include: 

- Coordination and communication 

- Delivery scheduling 

- Monitoring and tracking deliveries 

12. What are the key performance indicators (KPIs) for successful delivery operations? The key indicators include: 

   - Efficient and smooth delivery process 

   - Reduced workload for drivers 

   - Organized scheduling system 

13. What are the company’s priorities in terms of driver coordination and fleet maintenance? 

The cooperative prioritizes: 

- Basic vehicle maintenance (e.g., oil checks) 

- Driver responsibility for vehicle condition 

- Ensuring readiness of vehicles before operations 

## V. Digital Tools 

14. Based on the current challenges faced by the management, would you like to have a website that will efficiently and effectively perform these tasks? 

☑ Yes 

15. If yes, what expectations would you like to see using the website? The cooperative expects the website to: 

_STI College_ _**–** General Santos, Inc_ 

90 

   - Provide real-time monitoring and tracking (GPS-based) 

   - Allow tracking of delivery vehicle locations 

   - Improve coordination and communication 

   - Reduce manual workload and operational delays 

16. Using the website, how will it help your business in the market? The website is expected to: 

   - Improve operational efficiency 

   - Reduce time and effort in monitoring 

   - Enhance delivery processes 

   - Support overall business growth 

City Food Terminal Multi – purpose Cooperative (CFTMPC) 

## I. Informant Profile 

Name (Optional)/Alias: Not Specified Position: Cooperative Manager / Supervisor (implied) 

Gender: Not specified 

II. Cooperative Profile 

1. How many member-farmers are registered in the cooperative? Approximately 400 to 420 member-farmers are registered in the cooperative. 

2. How many vehicles for delivery are currently present in the management? At least one (1) wing van truck is used for delivery operations. 

3. What are the agricultural products handled? 

   - Mango (primary product) 

   - Vegetables 

   - Pumpkin 

   - Fish and meat (from different areas) 

4. What are the routes of service on delivery? 

   - Deliveries depend on production areas 

   - Products are sourced from areas like South Cotabato (e.g., Malungon, Kilinan, etc.) 

   - Distribution is within the Davao Region and nearby locations 

_STI College_ _**–** General Santos, Inc_ 

91 

III. Logistics Management 

5. What logistics or distribution management system is currently in place? 

The cooperative currently uses a manual logistics system, with no fully digital or virtual management system in place. Some POS and accounting software are used but are not integrated into logistics operations. 

6. How are transportation tasks such as scheduling, pooling requests, and route assignments currently managed? 

Transportation tasks are handled manually, with scheduling based on product availability and production levels. Deliveries are organized through designated dropping points and daily coordination. 

## 7. What are the challenges faced by the logistics/operations department? 

   - Lack of a digital or automated system 

   - Difficulty in tracking deliveries 

   - Product spoilage (e.g., overripe mangoes) 

   - High operational costs compared to sales 

   - No real-time monitoring (no GPS tracking) 

8. How are deliveries recorded and managed? 

Deliveries are recorded using a manual ticketing system, where transactions are tracked per sack or crate. There is no centralized digital tracking system. 

9. How are these challenges currently being addressed by the management? 

   - Use of mobile phones for communication 

   - Manual supervision by managers 

   - Efforts to reduce costs and improve product quality 

Logistics Needs and Goals 

10. What are the primary goals for improving your distribution efficiency in the succeeding year? 

   - Improve delivery routes and scheduling 

   - Ensure faster and more efficient distribution 

   - Minimize product spoilage 

   - Increase profitability 

11. What specific logistics processes need improvement? 

_STI College_ _**–** General Santos, Inc_ 

92 

   - Product handling and preservation 

   - Delivery coordination 

   - Tracking and monitoring system 

   - Route planning and scheduling 

12. What are the key performance indicators (KPIs) for successful delivery operations?  On-time delivery 

   - Good product quality upon arrival 

   - Minimal product damage 

   - Cost efficiency 

13. What are the company’s priorities in terms of driver coordination and fleet maintenance? 

- Regular vehicle inspection (battery, lights, etc.) 

- Driver coordination via mobile communication 

- Ensuring vehicles are in good condition before trips 

IV. Digital Tools 

14. Based on the current challenges faced by the management, would you like to have a website that will efficiently and effectively perform these tasks? 

☑ Yes 

15. If yes, what expectations would you like to see using the website? 

   - A modernized system for logistics management 

   - Real-time tracking and monitoring 

   - Organized data and reporting 

   - Improved communication and coordination 

16. Using the website, how will it help your business in the market? 

   - Improve operational efficiency 

   - Enhance product distribution 

   - Support marketing efforts (e.g., social media integration) 

   - Increase sales and overall business growth 

_STI College_ _**–** General Santos, Inc_ 

93 

## **APPENDIX H. PERSONAL TECHNICAL VITAE** 

_STI College_ _**–** General Santos, Inc_ 

94 

Curriculum Vitae of 

## Elnes Jake F. Gabales 

**Brangay Fatima Prk 21, Blk 22, Lot 1, General Santos City gabalesjake@gmail.com** 

**09759383383** 

## EDUCATIONAL BACKGROUND 

Level Inclusive Dates Name of school/ Institution Tertiary 2023-2027 STI College of Gensan Vocational/Technical N/A High School 2016-2023 Fatima National High School Elementary 2011-2016 Fatima Central Elementary School 

## PROFESSIONAL OR VOLUNTEER EXPERIENCE 

Nature of Experience/ Name and Address of Company or Inclusive Dates Job Title Organization N/A N/A N/A N/A 

**Listed in reverse chronological order (most recent first).** 

## AFFILIATIONS 

Inclusive Dates Name of Organization Position N/A N/A N/A N/A 

**Listed in reverse chronological order (most recent first).** 

_STI College_ _**–** General Santos, Inc_ 

95 

SKILLS 

SKILLS Level of Competency Date Acquired N/A TRAININGS, SEMINARS, OR WORKSHOPS ATTENDED Inclusive Dates Title of Training, Seminar, or Workshop 

N/A 

**Listed in reverse chronological order (most recent first).** 

_STI College_ _**–** General Santos, Inc_ 

96 

Curriculum Vitae of 

## Gabriel Andrei M. Lopez 

**Tanghal Homes lot 8, blk 40, Barangay San Isidro, General Santos City andreigabriellp24@gmail.com 09757700243** 

EDUCATIONAL BACKGROUND Level Inclusive Dates Name of school/ Institution Tertiary 2023-2027 STI College of Gensan Vocational/Technical N/A High School 2016-2023 King’s College of Isulan – STI College Tacurong Elementary 2011-2016 King’s College of Isulan 

## PROFESSIONAL OR VOLUNTEER EXPERIENCE 

Nature of Experience/ Name and Address of Company or Inclusive Dates Job Title Organization N/A N/A N/A N/A 

**Listed in reverse chronological order (most recent first).** 

## AFFILIATIONS 

Inclusive Dates Name of Organization Position N/A N/A N/A N/A 

**Listed in reverse chronological order (most recent first).** 

_STI College_ _**–** General Santos, Inc_ 

97 

SKILLS 

SKILLS Level of Competency 

Date Acquired N/A 

TRAININGS, SEMINARS, OR WORKSHOPS ATTENDED Inclusive Dates Title of Training, Seminar, or Workshop N/A 

**Listed in reverse chronological order (most recent first).** 

_STI College_ _**–** General Santos, Inc_ 

98 

Curriculum Vitae of 

## Ray Manuel C. Pineda 

**Perez Sub, Purok Malakas, Brangay San Isidro, General Santos City pineda.raymauel@gmail.com** 

**09126132333** 

EDUCATIONAL BACKGROUND Level Inclusive Dates Name of school/ Institution Tertiary 2023-2027 STI College of Gensan Vocational/Technical N/A High School 2010-2014 New Era University Elementary 2004-2009 New Era University 

## PROFESSIONAL OR VOLUNTEER EXPERIENCE 

Nature of Experience/ Name and Address of Company or Inclusive Dates Job Title Organization Feb 2021/ Oct Online Content Writer TD Media 2021 N/A N/A N/A 

**Listed in reverse chronological order (most recent first).** 

## AFFILIATIONS 

Inclusive Dates Name of Organization Position N/A N/A N/A N/A 

**Listed in reverse chronological order (most recent first).** 

_STI College_ _**–** General Santos, Inc_ 

99 

SKILLS 

SKILLS Level of Competency 

Date Acquired N/A 

TRAININGS, SEMINARS, OR WORKSHOPS ATTENDED Inclusive Dates Title of Training, Seminar, or Workshop N/A 

**Listed in reverse chronological order (most recent first).** 

_STI College_ _**–** General Santos, Inc_ 

100 

Curriculum Vitae of 

## Iver Jude E. Relox 

**Camp Fermin G. Lira, Barangay Dadiangas West, General Santos City reloxiver25@gmail.com** 

**09273615984** 

EDUCATIONAL BACKGROUND Level Inclusive Dates Name of school/ Institution Tertiary 2023-2027 STI College of Gensan Vocational/Technical N/A High School 2016-2023 GSC SPED Integrated School Elementary 2011-2016 Tacurong Pilot Elementary School – GSC SDA Elementary School 

## PROFESSIONAL OR VOLUNTEER EXPERIENCE 

Nature of Experience/ Name and Address of Company or Inclusive Dates Job Title Organization N/A N/A N/A N/A 

**Listed in reverse chronological order (most recent first).** 

## AFFILIATIONS 

Inclusive Dates Name of Organization Position N/A N/A N/A N/A 

**Listed in reverse chronological order (most recent first).** 

_STI College_ _**–** General Santos, Inc_ 

101 

SKILLS SKILLS Level of Competency Date Acquired N/A TRAININGS, SEMINARS, OR WORKSHOPS ATTENDED Inclusive Dates Title of Training, Seminar, or Workshop N/A 

**Listed in reverse chronological order (most recent first).** 

_STI College_ _**–** General Santos, Inc_ 

102 

