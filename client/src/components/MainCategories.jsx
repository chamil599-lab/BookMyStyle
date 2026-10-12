import { Link } from "react-router-dom";

export default function MainCategories() {
  return (
    <div className="hidden:md flex bg-gray-100 rounded-3xl xl:rounded-full p-4 items-center justify-center gap-8">
        <div className="flex-1 flex items-center justify-between flex-wrap">
            <Link to="/post" className="bg-blue-600 text-white px-4 py-2 rounded-full hover:bg-blue-700 transition-colors duration-300">
            All Posts</Link>
            <Link to="/post?cat=web-design" className=" px-4 py-2 rounded-full hover:bg-blue-100 transition-colors duration-300">
            Web Design</Link>
            <Link to="/post?cat=development" className=" px-4 py-2 rounded-full hover:bg-blue-100 transition-colors duration-300">
            Development</Link>
            <Link to="/post?cat=database" className=" px-4 py-2 rounded-full hover:bg-blue-100 transition-colors duration-300">
            Database</Link>
            <Link to="/post?cat=search-engine" className=" px-4 py-2 rounded-full hover:bg-blue-100 transition-colors duration-300">
            Search Engine</Link>
            <Link to="/post?cat=marketing" className=" px-4 py-2 rounded-full hover:bg-blue-100 transition-colors duration-300">
            Marketing</Link>
        </div>
        <span className="text-xl font-medium">|</span>
        <div className="bg-white p-2 rounded-full flex items-center gap-2">
            <svg 
            xmlns="http://www.w3.org/2000/svg" 
            className="h-6 w-6 text-gray-600" 
            fill="none"
            stroke="gray-600">
                <circle cx="11" cy="11" r="8" stroke="currentColor" strokeWidth="2"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65" stroke="currentColor" strokeWidth="2"></line>
            </svg>
            <input type="text" placeholder="Search a post..." className="outline-hidden"/>
        </div>
    </div>
  )
}
